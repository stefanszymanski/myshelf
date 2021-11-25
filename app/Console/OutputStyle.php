<?php

namespace App\Console;

use App\Validator\NewKeyValidator;
use App\Validator\NotEmptyValidator;
use SleekDB\Store;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Helper\Helper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OutputStyle extends \Illuminate\Console\OutputStyle
{
    private OutputInterface $output;

    private InputInterface $input;

    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);
        $this->output = $output;
        $this->input = $input;
    }
    protected function record(array $headers, array $record): void
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

    public function title(string $message)
    {
        $this->newLine();
        $this->output->writeln([
            sprintf(' <comment>%s</>', OutputFormatter::escapeTrailingBackslash($message)),
            sprintf(' <comment>%s</>', str_repeat('─', Helper::width(Helper::removeDecoration($this->output->getFormatter(), $message)))),
        ]);
        $this->newLine();
    }

    /**
     * Ask a question until the answer is not empty.
     */
    public function askMandatory(string $question, ?string $default = null)
    {
        return $this->askWithValidation($question, [new NotEmptyValidator], $default);
    }

    /**
     * Ask for a key.
     *
     * The key must not be empty and must be unique inside a given store.
     */
    public function askForKey(string $question, Store $store, ?string $default = null)
    {
        return $this->askWithValidation($question, [new NewKeyValidator($store)], $default);
    }

    /**
     * Ask with one or more validators.
     *
     * @param string $question
     * @param array<callable> $validators
     * @param string|null $default
     */
    protected function askWithValidation(string $question, array $validators, ?string $default = null)
    {
        return $this->ask($question, $default, function($answer) use ($validators) {
            foreach ($validators as $validator) {
                $answer = $validator($answer);
            }
            return $answer;
        });
    }
}
