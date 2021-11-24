<?php

namespace App\Console\Dialog;

use App\Utility\RecordUtility;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class PersonDialog extends AbstractRecordDialog
{
    /**
     * Build autocomplete options.
     *
     * Fetches all persons and build two autocomplete options for each:
     * {firstname} {lastname}
     * {lastname}, {firstname}
     */
    protected function buildAutocompleteOptions(): array
    {
        $records = $this->db->persons()->createQueryBuilder()
            ->select(['key', 'firstname', 'lastname'])
            ->getQuery()
            ->fetch();
        $options = [];
        foreach ($records as $record) {
            $options["{$record['firstname']} {$record['lastname']}"] = $record['key'];
            $options["{$record['lastname']}, {$record['firstname']}"] = $record['key'];
        }
        return $options;
    }

    /**
     * Parse the user input from the record selection into record default values.
     *
     * Splits the input into firstname and lastname.
     *
     * @param string $value The user input
     * @return array Record defaults
     */
    protected function getDefaultsFromInput(string $value): array
    {
        if (str_contains($value, ',')) {
            // If the user input contains a comma: use the first part as lastname and the rest as firstname.
            list($lastname, $firstname) = array_map('trim', explode(',', $value, 2));
        } else {
            // Otherwise use everything before the last space as firstname and the rest as lastname.
            $parts = explode(' ', $value);
            $lastname = trim(array_pop($parts));
            $firstname = trim(implode(' ', $parts));
        }
        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
        ];
    }

    /**
     * Create and persist a record.
     *
     * @param array $defaults Record default values
     * @return string Key of the new record
     */
    public function createRecord(array $defaults = []): string
    {
        $type = $this->configuration->getType('person');
        $labels = $type->getFieldLabels(['key', 'firstname', 'lastname', 'nationality']);

        $this->headline('New person record');

        $firstname = $this->ask($labels['firstname'], $defaults['firstname'] ?? null);
        $lastname = $this->askMandatory($labels['lastname'], $defaults['lastname'] ?? null);
        $nationality = $this->ask($labels['nationality'], $defaults['nationality'] ?? null);
        $defaultKey = RecordUtility::createKey($firstname, $lastname);
        $key = $this->askForKey($labels['key'], $this->db->persons(), $defaultKey);

        $record = [
            'key' => $key,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'nationality' => $nationality,
        ];

        $this->output->writeln(' <info>You entered the following data:</info>');
        $this->renderRecord($labels, $record);

        $repeat = $this->confirm('Do you want to change something?', false);
        if ($repeat) {
            $this->createRecord($record);
        }

        $this->db->persons()->insert($record);
        $this->output->success('The record was created.');

        return $key;
    }

    // TODO move to a custom OutputStyle class
    protected function headline(string $message): void
    {
        $this->newLine();
        $this->output->writeln([
            sprintf(' <comment>%s</>', OutputFormatter::escapeTrailingBackslash($message)),
            sprintf(' <comment>%s</>', str_repeat('─', Helper::width(Helper::removeDecoration($this->output->getFormatter(), $message)))),
        ]);
        $this->newLine();
    }

    // TODO move to a custom OutputStyle class
    protected function renderRecord(array $headers, array $record): void
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

}
