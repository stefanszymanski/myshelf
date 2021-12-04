<?php

declare(strict_types=1);

namespace App;

class Layer
{
    /**
     * @internal
     */
    public function __construct(protected Context $context, protected string $text)
    {
    }

    /**
     * @internal
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Update the screen for the layer.
     *
     * @param string|null $text An updated text for the breadcrumb
     * @return void
     */
    public function update(?string $text = null): void
    {
        if ($text) {
            $this->text = $text;
        }
        $this->context->updateLayer($this);
    }

    /**
     * Close the layer.
     *
     * @return void
     */
    public function finish(): void
    {
        $this->context->finishLayer($this);
    }
}
