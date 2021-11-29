<?php

namespace App\Commands;

use App\Console\Dialog\CreateDialog;
use App\Persistence\Database;
use App\Persistence\Table as PersistenceTable;
use App\Utility\RecordUtility;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;

class AddCommand extends Command
{
    protected $signature = 'add {table}';

    protected $description = 'Create a new record';

    protected PersistenceTable $table;

    public function __construct(protected Database $db)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $tableName = $this->argument('table');
        $this->table = $this->db->getTable($tableName);

        $this->headline(sprintf('New %s record', $tableName));

        $dialog = new CreateDialog($this->input, $this->output, $this->db, $this->table);
        $record = $this->options();

        do {
            $record = $dialog->run($record);
            // TODO refactor line
            $this->output->writeln(' <info>You entered the following data:</info>');
            // TODO move displayRecord() to a separate class
            $this->displayRecord($this->table->getFieldLabels(array_keys($record)), $record);
            $repeat = $this->confirm('Do you want to change something?', false);
        } while ($repeat);

        $this->table->store->insert($record);
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
