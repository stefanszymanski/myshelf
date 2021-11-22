<?php

namespace App\Domain\Type;

use App\Database;

class Person extends AbstractType
{
    protected function configure(): void
    {
        // TODO more join fields: books-as-editor, publishers, publishers-as-editor
        $this
            ->registerField(
                name: 'firstname',
                label: 'First name'
            )
            ->registerField(
                name: 'lastname',
                label: 'Last name'
            )
            ->registerField(
                name: 'nationality',
                label: 'Nationality'
            )

            ->registerVirtualField(
                name: 'name',
                label: 'Full name',
                description: 'Last name and first name concatenated: `{lastname}, {firstname}`',
                select: ['CONCAT' => [', ', 'lastname', 'firstname']]
            )
            ->registerVirtualField(
                name: 'name2',
                label: 'Full name',
                description: 'First name and last name concatenated: `{firstname} {lastname}`',
                select: ['CONCAT' => [' ', 'firstname', 'lastname']]
            )

            ->registerJoinField(
                name: 'books',
                label: 'Books',
                description: 'Number of books the person is an author of',
                select: ['LENGTH' => '_books'],
                joinAs: '_books',
                join: function(Database $db) {
                    return fn ($person) => $db->books()->findBy(['authors', 'CONTAINS', $person['key']]);
                }
            );
    }
}
