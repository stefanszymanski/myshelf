<?php

declare(strict_types=1);

namespace App\Persistence\Data;

use App\Context;
use Symfony\Component\Console\Question\Question;

class InputField extends AbstractField
{
    /**
     * {@inheritDoc}
     */
    public function askForValue(Context $context, mixed $defaultValue): mixed
    {
        $prompt = ucfirst($this->getLabel());
        $question = new Question($prompt, $defaultValue);
        $question->setValidator($this->validator);
        return $context->output->askQuestion($question);
    }
}
