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
                'table' => $this->table->getLabel(),
            ]),
        );
        $layer->update();

        $fields = $this->table->getDataFields();
        $record = [
            'data' => [],
            'meta' => [],
        ];

        // Ask for fields defined by the schema.
        foreach ($this->table->getNewRecordDialogFields() as $fieldName => $field) {
            $default = $defaults[$fieldName] ?? null;
            $value = $field->askForValue($this->context, $default);
            $record['data'][$fieldName] = $value;
        }

        // Let the user edit the newly created record.
        $editDialog = new EditRecordDialog($this->context, $this->table);
        $record = $editDialog->render($record);
        $record['meta']['created'] = time();

        $layer->finish();
        return $record;
    }
}
