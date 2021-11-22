<?php

namespace App\Domain\Type;

use App\Database;

class Book extends AbstractType
{
    protected function configure(): void
    {
        $this
            ->registerField(
                name: 'title',
                label: 'Title'
            )
            ->registerField(
                name: 'published',
                label: 'Published'
            )
            ->registerField(
                name: 'acquired',
                label: 'Acquired'
            )

            ->registerJoinField(
                name: 'authors',
                label: 'Authors',
                description: 'Names of the authors',
                select: function ($book) {
                    $authors = array_map(fn ($author) => sprintf('%s %s', $author['firstname'], $author['lastname']), $book['_authors']);
                    return implode(', ', $authors);
                },
                joinAs: '_authors',
                join: function (Database $db) {
                    return fn ($book) => $db->persons()->findBy(['key', 'IN', $book['authors']]);
                }
            /* ) */
            /* ->registerJoinField( */
            /*     name: 'author', */
            /*     label: 'Author', */
            /*     description: 'Author name', */
            /*     select: 'TODO', */
            /*     joinAs: '_author', */
            /*     join: function (Database $db) { */
            /*         return function ($book) use ($db) { */
            /*             $authors = $db->persons()->findBy(['key', 'IN', $book['authors']]); */
            /*         }; */
            /*     } */
            );

        // TODO add a author variant, that creates a record for each author of a book. That would be useful for grouping by.
        //      Otherwise grouping by "authors" would create separate groups for books with multiple authors.

        // TODO evaluate what is needed to configure multi value fields for filtering. E.g. filter books by a specific author
    }
}
