<?php

declare(strict_types=1);

namespace App\Console;

class CreateRecordDialog extends Dialog
{
    /**
     * Render the dialog.
     *
     * @param array<string,mixed> $defaults
     * @return array<string,mixed>
     */
    public function render(array $defaults = []): ?array
    {
        $layer = $this->context->addLayer(
            __('breadcrumb.createrecord', [
                'table' => __(sprintf('schema.%s.label', $this->table->name)),
            ]),
        );
        $layer->update();

        $fields = $this->table->getDataFields();
        $record = [];

        // Ask for fields defined by the schema.
        foreach ($this->table->getNewRecordDialogFields() as $fieldName => $field) {
            $default = $defaults[$fieldName] ?? null;
            $value = $field->askForValue($this->context, $default);
            $record[$fieldName] = $value;
        }

        // Let the user edit the newly created record.
        $editDialog = new EditRecordDialog($this->context, $this->table);
        $record = $editDialog->render($record);

        $layer->finish();
        return $record;
    }
}
