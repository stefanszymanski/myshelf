<?php

declare(strict_types=1);

namespace App\Console;

use App\Validator\NewKeyValidator;

class CreateRecordDialog extends Dialog
{
    /**
     * @param array<string,mixed> $defaults
     * @return array<string,mixed>
     */
    public function render(array $defaults = []): ?array
    {
        $layer = $this->context->addLayer(sprintf('Create new %s', $this->table->getLabel()));
        $layer->update();

        $fields = $this->table->getFields2();
        $record = [];

        // Ask for fields defined by the schema.
        foreach ($fields as $field) {
            $name = $field->name;
            $default = $defaults[$name] ?? null;
            $value = $field->ask($this->context, $default);
            $record[$name] = $value;
        }

        // Ask for a key.
        $record['key'] = $this->output->ask(
            'Key',
            $this->table->createKeyForRecord($record),
            new NewKeyValidator($this->table->store)
        );

        // Let the user edit the newly created record.
        $editDialog = new EditRecordDialog($this->context, $this->table);
        $record = $editDialog->render($record);

        $layer->finish();
        return $record;
    }
}
