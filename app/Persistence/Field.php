<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Context;
use App\Utility\RecordUtility;
use App\Validator\ValidationException;
use Symfony\Component\Console\Question\Question;

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

    /**
     * Get value for an empty field state.
     *
     * @return null
     */
    public function getEmptyValue(): mixed
    {
        return null;
    }

    /**
     * Ask the user for a value.
     *
     * @param Context $context
     * @param mixed $defaultAnswer
     * @return mixed
     */
    public function ask(Context $context, mixed $defaultAnswer = null): mixed
    {
        if (!$this->question) {
            $question = $this->createDefaultQuestion($defaultAnswer);
            return $context->output->askQuestion($question);
        }
        return $defaultAnswer;
    }

    /**
     * Check whether a value is allowed by the field.
     *
     * @param mixed $value
     * @return bool
     */
    public function validate(mixed $value): bool
    {
        // TODO doesnt work with ReferenceField because it doesnt have a property $validators
        //      How to solve this problem? Clearing multi-reference fields shouldnt clear all references, but let the user select
        //      which references to remove.
        try {
            foreach ($this->validators as $validatorFactory) {
                call_user_func($validatorFactory)($value);
            }
        } catch (ValidationException $e) {
            return false;
        }
        return true;
    }

    /**
     * Convert a value to a printable representation.
     *
     * @param mixed $value
     * @return string
     */
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
