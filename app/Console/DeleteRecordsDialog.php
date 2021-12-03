<?php

declare(strict_types=1);

namespace App\Console;

use App\Persistence\Database;
use App\Persistence\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DeleteRecordsDialog
{
    public function __construct(protected InputInterface $input, protected SymfonyStyle $output, protected Database $db, protected Table $table)
    {
    }

    /**
     * Render a records deletion dialog.
     *
     * @param array<string|int> $keysOrIds Record keys or IDS
     * @return void
     */
    public function render(array $keysOrIds): void
    {
        // TODO if there are referring records, display them and ask the user if:
        //      - the whole records should be deleted
        //      - the references in these records should be removed
        //      - the deletion should be aborted

        list($keys, $invalidKeys) = $this->getKeys($keysOrIds);
        if (!empty($invalidKeys)) {
            $this->output->error(sprintf('Invalid keys: %s', implode(', ', $invalidKeys)));
            return;
        }

        $referringRecords = $this->getReferringRecords($keys);

        // If there aren't any referring records, ask for confirmation and delete.
        if (empty($referringRecords)) {
            $this->output->text([
                'There are no referring records.',
                '',
                'Following records will be deleted:'
            ]);
            // TODO render a prettier list with more than just the record keys
            $this->output->listing($keys);
            $confirmed = $this->output->confirm('Do you really want to delete them?', false);
            if ($confirmed) {
                foreach (array_keys($keys) as $id) {
                    $this->table->store->deleteById($id);
                }
                $this->output->success('Records deleted');
            }
            return;
        }

        foreach ($keys as $id => $key) {
            $_referringRecords = $referringRecords[$key] ?? null;
            if (!$_referringRecords) {
                /* $this->output->info("No referring records for '$key'"); */
                continue;
            }
            foreach ($_referringRecords as $fieldName => $__referringRecords) {
                $this->output->warning("There are referring records for '$key [$id]' on field '$fieldName'");
                // TODO render prettier table
                $this->output->table(['ID', 'Key'], $__referringRecords);
            }
            $this->output->error('No records where deleted due to referring records');
        }
    }

    /**
     * Determine record ID and key for each given ID or key.
     *
     * @param array<string|int> $keysOrIds
     * @return array{array<int,string>,array<string|int>} First element are pairs of record IDs and their key, second element is a list of invalid keys and IDs
     */
    protected function getKeys(array $keysOrIds): array
    {
        $keys = [];
        $invalidKeys = [];
        foreach ($keysOrIds as $keyOrId) {
            $record = $this->table->findByKeyOrId($keyOrId);
            if (!$record) {
                $invalidKeys[] = $keyOrId;
            } else {
                $keys[$record['id']] = $record['key'];
            }
        }
        return [$keys, $invalidKeys];
    }

    /**
     * Determine referring records for the given record keys.
     *
     * @param array<string> $keys
     * @return array<string,array<array<string,mixed>>> Keys are record keys, values are lists of their referring records
     */
    protected function getReferringRecords(array $keys): array
    {
        $records = [];
        foreach ($keys as $key) {
            $_records = $this->table->findReferringRecords($key);
            if (!empty($_records)) {
                $records[$key] = $_records;
            }
        }
        return $records;
    }
}
