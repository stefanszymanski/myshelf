<?php

declare(strict_types=1);

namespace App\Console\Dialog\Book;

use App\Console\Dialog\AbstractDialog;
use App\Utility\RecordUtility;
use App\Validator\IntegerValidator;
use App\Validator\LooseDateValidator;

class CreateDialog extends AbstractDialog
{
    public function run(array $defaults = []): array
    {
        $title = $this->askMandatory('Title', $defaults['title'] ?? null);

        // TODO support default values when asking for records
        $authors = $this->askForRecords('person', 'author');
        $editors = $this->askForRecords('person', 'editor');
        $publisher = $this->askForRecord('publisher', 'publisher');

        $published = $this->askWithValidation('Published', [new IntegerValidator], $default['published'] ?? null);
        $acquired = $this->askWithValidation('Acquired', [new LooseDateValidator], $defaults['acquired'] ?? null);

        $defaultKey = RecordUtility::createKey($authors[0] ?? $editors[0] ?? null, $title);
        $key = $this->askForKey('Key', $defaultKey);

        return [
            'key' => $key,
            'title' => $title,
            'authors' => $authors,
            'editors' => $editors,
            'publisher' => $publisher,
            'published' => $published,
            'acquired' => $acquired,
        ];
    }
}

