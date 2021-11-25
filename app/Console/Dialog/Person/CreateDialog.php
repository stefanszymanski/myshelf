<?php

declare(strict_types=1);

namespace App\Console\Dialog\Person;

use App\Console\Dialog\AbstractDialog;
use App\Utility\RecordUtility;

class CreateDialog extends AbstractDialog
{
    public function run(array $defaults = []): array
    {
        $labels = $this->type->getFieldLabels(['key', 'firstname', 'lastname', 'nationality']);

        $firstname = $this->ask($labels['firstname'], $defaults['firstname'] ?? null);
        $lastname = $this->askMandatory($labels['lastname'], $defaults['lastname'] ?? null);
        $nationality = $this->ask($labels['nationality'], $defaults['nationality'] ?? null);
        $defaultKey = RecordUtility::createKey($firstname, $lastname);
        $key = $this->askForKey($labels['key'], $defaultKey);

        return [
            'firstname' => $firstname,
            'lastname' => $lastname,
            'nationality' => $nationality,
            'key' => $key,
        ];
    }
}
