<?php

declare(strict_types=1);

namespace App;

use App\Persistence\Database;
use App\Persistence\Table;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class Context
{
    /**
     * @var array<array{Layer,callable|null}>
     */
    protected array $layers = [];

    /**
     * @var array<callable>
     */
    protected array $queue = [];

    /**
     * @var Cursor
     */
    protected Cursor $cursor;


    // TODO replace SymfonyStyle with OutputInterface?
    public function __construct(
        public readonly InputInterface $input,
        public readonly SymfonyStyle $output,
        public readonly Database $db,
    ) {
        $this->cursor = new Cursor($this->output, $this->input);
    }

    /**
     * Add a new layer on top of the previous ones.
     *
     * @param string $text Text for the breadcrumb
     * @param callable|null $callback Function to execute right after the breadcrumb was displayed
     * @return Layer The newly created layer
     */
    public function addLayer(string $text, ?callable $callback = null): Layer
    {
        $layer = new Layer($this, $text);
        $this->layers[] = [$layer, $callback];
        return $layer;
    }

    /**
     * Enqueue some code that is executed on the next layer update.
     *
     * This is used for deferring output.
     *
     * @param callable $callback
     * @return void
     */
    public function enqueue(callable $callback): void
    {
        $this->queue[] = $callback;
    }

    /**
     * Execute all queue elements.
     *
     * @return void
     */
    protected function runQueue(): void
    {
        foreach ($this->queue as $function) {
            call_user_func($function);
        }
    }

    public function flush(): void
    {
        $this->layers = [];
        $this->runQueue();
        $this->clearQueue();
    }

    /**
     * Clear the queue.
     *
     * @return void
     */
    protected function clearQueue(): void
    {
        $this->queue = [];
    }

    /**
     * @internal
     */
    public function updateLayer(Layer $layer): void
    {
        if (!in_array($layer, array_column($this->layers, 0))) {
            throw new \InvalidArgumentException('The given layer must be active to get updated');
        }
        $this->updateScreen();
    }

    /**
     * @internal
     * TODO refactor: less insane array key detection
     */
    public function finishLayer(Layer $layer): void
    {
        if (!in_array($layer, array_column($this->layers, 0))) {
            throw new \InvalidArgumentException('The given layer must be active to get finished');
        }
        // Check if the given layer is on top of the stack.
        if (array_search($layer, array_column($this->layers, 0)) !== array_key_last(array_column($this->layers, 0))) {
            throw new \InvalidArgumentException('Only the layer on top of the stack can get finished');
        }
        // Unset the layer on top of the stack.
        unset($this->layers[array_key_last($this->layers)]);
    }

    /**
     * Clear the screen, display the breadcrumb and the active layer.
     *
     * @return void
     */
    protected function updateScreen(): void
    {
        $this->cursor->clearScreen();
        $this->cursor->moveToPosition(0, 0);
        if (!empty($this->layers)) {
            $this->renderBreadcrumb();
            $layerCallback = $this->layers[array_key_last($this->layers)][1];
            if ($layerCallback) {
                call_user_func($layerCallback);
            }
            $this->runQueue();
            $this->clearQueue();
        }
    }

    /**
     * Display the breadcrumb.
     *
     * @return void
     */
    protected function renderBreadcrumb(): void
    {
        $texts = array_map(fn (Layer $layer) => sprintf('<fg=blue>%s</>', $layer->getText()), array_column($this->layers, 0));
        $breadcrumb = implode(' > ', $texts);
        $this->output->writeln([
            '',
            " $breadcrumb",
            '',
        ]);
    }
}
