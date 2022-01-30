<?php

declare(strict_types=1);

namespace App\Console;

class DeleteRecordsDialog extends Dialog
{
    /**
     * Render a records deletion dialog.
     *
     * @param array<int> $ids Record IDs
     * @return array<int> IDs of deleted records
     */
    public function render(int ...$ids): array
    {
        // TODO refactor: no multiple returns anymore
        // TODO if there are referring records, display them and ask the user if:
        //      - the whole records should be deleted
        //      - the references in these records should be removed
        //      - the deletion should be aborted

        $referringRecords = $this->getReferringRecords($ids);

        // If there aren't any referring records, ask for confirmation and delete.
        if (empty($referringRecords)) {
            $this->output->text([
                'There are no referring records.',
                '',
                'Following record(s) will be deleted:'
            ]);
            // TODO render a prettier list with more than just the record keys
            $this->output->listing($ids);
            $confirmed = $this->output->confirm('Do you really want to delete them?', false);
            if ($confirmed) {
                foreach (array_keys($ids) as $id) {
                    $this->table->store->deleteById($id);
                }
                $this->success('Record(s) deleted');
                return $ids;
            } else {
                return [];
            }
        }

        foreach ($ids as $id) {
            if (!isset($referringRecords[$id])) {
                /* $this->output->info("No referring records for '$key'"); */
                continue;
            }
            $statistics = collect($referringRecords[$id])
                ->map(
                    fn ($records) => collect($records)
                        ->map(fn ($ids) => count($ids))
                        ->sum()
                )
                ->map(fn ($count, $tableName) => sprintf(
                    '%s from %s',
                    $count,
                    $this->db->getTable($tableName)->getLabel()
                ))
                ->implode(', ');
            $this->warning(sprintf(
                'There are referring records for %s #%s on following tables: %s',
                $this->table->getLabel(),
                $id,
                $statistics,
            ));
            // TODO render a table of found records as in ListCommand, using the default query fields
            //      but Table::find() cannot be used to search by IDs, yet.
            /* $this->context->enqueue(fn () => $this->output->table(['ID'], $__referringRecords)); */
        }
        $this->error('No records were deleted due to referring records');

        return [];
    }

    /**
     * Determine referring records for the given record keys.
     *
     * @param array<int> $ids
     * @return array<int,array<string,array<string,array<int>>>>
     */
    protected function getReferringRecords(array $ids): array
    {
        return collect($ids)
            ->mapWithKeys(fn ($id) => [$id => $this->table->findReferringRecords($id)])
            ->filter()
            ->all();
    }
}
