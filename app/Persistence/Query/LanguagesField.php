<?php

declare(strict_types=1);

namespace App\Persistence\Query;

use App\Persistence\Database;
use App\Persistence\Table;
use Illuminate\Support\Arr;
use SleekDB\QueryBuilder;
use UnexpectedValueException;

/**
 * This Query Field formats the fields `language` and `origlanguage`.
 *
 * If either `language` or `origlanguage` are set or both are the same, the
 * value of one of them is shown.
 * If both are set but different, both values are displayed as
 * "{language} ← {origlanguage}".
 */
class LanguagesField extends AbstractField
{
    /**
     * @param string $label
     */
    public function __construct(
        protected readonly string $label,
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function modifyQuery(QueryBuilder $qb, string $alias, ?string $queryFieldPath, Database $db, Table $table): QueryBuilder
    {
        $formatLanguage = $table->getDataField('language')->formatValue(...);
        $formatOrigLanguage = $table->getDataField('origlanguage')->formatValue(...);
        // Merge the book language with those from the content, also include the original language if it differs.
        return $qb->select([
            $alias => fn ($record) => collect()
                // Add the record language.
                ->add([Arr::get($record, 'data.language'), Arr::get($record, 'data.origlanguage')])
                // Replace the language values by their labels.
                ->map(fn ($pair) => [$formatLanguage($pair[0]), $formatOrigLanguage($pair[1])])
                // Format to include either the language, original language or both.
                ->map(fn ($pair) => match (true) {
                    !$pair[0] && !$pair[1] => null,
                    !$pair[1] => $pair[0],
                    !$pair[0] => $pair[1],
                    $pair[0] == $pair[1] => $pair[0],
                    $pair[0] != $pair[1] => sprintf("%s \u{2190} %s", $pair[0], $pair[1]),
                    default => throw new UnexpectedValueException('This should not happen.')
                })
                ->filter()
                ->unique()
                ->implode("\n")
        ]);
    }
}
