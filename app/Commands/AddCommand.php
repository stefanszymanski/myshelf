<?php

namespace App\Commands;

use App\Configuration;
use App\Console\Dialog\CreateDialogTrait;
use App\Domain\Type\TypeInterface;
use App\Repository;
use App\Utility\RecordUtility;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class AddCommand extends Command
{
    use CreateDialogTrait;

    protected Repository $repository;

    protected TypeInterface $type;

    protected $signature = 'add {type}';

    protected $description = 'Create a new record';

    public function __construct(protected Configuration $configuration)
    {
        parent::__construct();
    }

    public function handle()
    {
        $typeName = $this->argument('type');
        $this->repository = $this->configuration->getRepository($typeName);
        $this->type = $this->configuration->resolveType($typeName);

        $this->headline("New $typeName record");

        $dialog = $this->getCreateDialog($typeName);
        $record = $this->options();

        do {
            $record = $dialog->run($record);
            $this->output->writeln(' <info>You entered the following data:</info>');
            $this->displayRecord($this->type->getFieldLabels(array_keys($record)), $record);
            $repeat = $this->confirm('Do you want to change something?', false);
        } while ($repeat);

        $this->repository->getStore()->insert($record);
        $this->output->success('The record was created.');
    }

    /**
     * Display a record summary.
     *
     * @param array<string> $headers
     * @param array<string,mixed> $record
     * @return void
     */
    protected function displayRecord(array $headers, array $record): void
    {
        // Configure a custom style, because per default the top border uses incorrect characters.
        $style = (new TableStyle())
            ->setHorizontalBorderChars('─')
            ->setVerticalBorderChars('│')
            ->setCrossingChars('┬', '┌', '┬', '┐', '┐', '┘', '┴', '└', '┌')
            ->setCellHeaderFormat('<info>%s</info>');

        $row = array_map([RecordUtility::class, 'convertToString'], $record);
        $rows = [$row];

        $table = new Table($this->output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->setStyle($style);
        $table->setHorizontal(true);
        $table->render();
        $this->newLine();
    }

    /**
     * Display a headline.
     */
    public function headline(string $message): void
    {
        $this->newLine();
        $this->output->writeln([
            sprintf(' <comment>%s</>', OutputFormatter::escapeTrailingBackslash($message)),
            sprintf(' <comment>%s</>', str_repeat('─', Helper::width(Helper::removeDecoration($this->output->getFormatter(), $message)))),
        ]);
        $this->newLine();
    }

}
