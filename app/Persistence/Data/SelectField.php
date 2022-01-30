<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Context;
use App\Validator\Validator;
use Symfony\Component\Console\Question\ChoiceQuestion;

class SelectField extends AbstractField
{
    /**
     * @param array<string,string> $options Keys are stored, values are labels
     * @param Validator $validator
     * @param string $label
     */
    public function __construct(
        protected readonly array $options,
        protected readonly Validator $validator,
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function formatValue(mixed $value): string
    {
        return match (true) {
            empty($value) => '',
            array_key_exists($value, $this->options) => $this->options[$value],
            default => sprintf('<error>%s</>', $value)
        };
    }

    /**
     * {@inheritDoc}
     */
    public function askForValue(Context $context, mixed $defaultValue): mixed
    {
        $prompt = ucfirst($this->getLabel());
        $optionLabels = array_values($this->options);
        $defaultOptionValue = collect($this->options)->keys()->search($defaultValue);
        $answer = $context->output->askQuestion(new ChoiceQuestion($prompt, $optionLabels, $defaultOptionValue));
        return collect($this->options)->search($answer);
    }
}
