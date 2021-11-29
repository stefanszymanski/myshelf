<?php

declare(strict_types=1);

namespace App\Console\Dialog;

use App\Persistence\Database;
use App\Persistence\Table;
use App\Validator\NewKeyValidator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// TODO move to App\Console\CreateDialog
class CreateDialog
{
    public function __construct(protected InputInterface $input, protected SymfonyStyle $output, protected Database $db, protected Table $table)
    {
    }

    /**
     * @param array<string,mixed> $defaults
     * @return array<string,mixed>
     */
    public function run(array $defaults = []): array
    {
        $fields = $this->table->getFields2();
        $record = [];
        // Ask for fields defined by the schema.
        foreach ($fields as $field) {
            $name = $field->name;
            $default = $defaults[$name] ?? null;
            $value = $field->ask($this->input, $this->output, $this->db, $default);
            $record[$name] = $value;
        }
        // Ask for a key.
        $record['key'] = $this->output->ask(
            'Key',
            $this->table->createKeyForRecord($record),
            new NewKeyValidator($this->table->store)
        );
        return $record;
    }
}
