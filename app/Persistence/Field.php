<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Utility\RecordUtility;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;

class Field
{
    public function __construct(
        public readonly string $table,
        public readonly string $name,
        public readonly string $label,
        public readonly ?string $description = null,
        protected ?\Closure $question = null,
        protected array $validators = [],
        protected ?\Closure $formatter = null,
    ) {
    }

    public function ask(InputInterface $input, SymfonyStyle $output, Database $db, mixed $defaultAnswer = null): mixed
    {
        if (!$this->question) {
            $question = $this->createDefaultQuestion($defaultAnswer);
            return $output->askQuestion($question);
        }
        return $defaultAnswer;
    }

    public function valueToString(mixed $value): string
    {
        return $this->formatter
            ? call_user_func($this->formatter, $value)
            : RecordUtility::convertToString($value);
    }

    protected function createDefaultQuestion(mixed $defaultAnswer = null): Question
    {
        $question = new Question($this->label, $defaultAnswer);
        if (!empty($this->validators)) {
            $question->setValidator((function($answer) {
                foreach ($this->validators as $validatorFactory) {
                    $answer = call_user_func($validatorFactory)($answer);
                }
                return $answer;
            })->bindTo($this));
        }
        return $question;
    }
}
