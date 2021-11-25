<?php

declare(strict_types=1);

namespace App\Console\Dialog;

trait CreateDialogTrait
{
    /**
     * Get a Create Dialog for the given type.
     *
     * @param string $type
     * @return DialogInterface
     */
    protected function getCreateDialog(string $type): DialogInterface
    {
        $className = sprintf('\\App\\Console\\Dialog\\%s\\CreateDialog', ucfirst($type));
        return new $className($this->input, $this->output, $this->configuration);
    }
}
