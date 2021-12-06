<?php

declare(strict_types=1);

namespace App\Persistence;

use App\Console\RecordSelector;
use App\Context;

class ReferenceField extends Field
{
    public function __construct(
        public readonly string $table,
        public readonly string $name,
        public readonly string $foreignTable,
        public readonly string $label,
        public readonly ?string $description = null,
        protected ?\Closure $formatter = null,
        protected array $validators = [],
    ) {
    }

    /**
     * Get value for an empty field state.
     *
     * @return mixed
     */
    public function getEmptyValue(): mixed
    {
        return null;
    }

    /**
     * Ask the user for a record with autocompletion.
     *
     * @return string|null Key of the selected record
     */
    public function ask(Context $context, mixed $defaultAnswer = null): mixed
    {
        $question = preg_match('/^[aeiou]/i', $this->label)
            ? 'Select an %s'
            : 'Select a %s';
        return (new RecordSelector(
            $context,
            $context->db->getTable($this->foreignTable))
        )->render(sprintf($question, $this->label), $defaultAnswer);
    }
}
