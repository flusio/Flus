<?php

namespace flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sorter
{
    /**
     * Sort items by the given property, based on the current locale.
     *
     * @template T of object
     *
     * @param T[] $items
     *
     * @return T[]
     */
    public static function localeSort(array $items, string $property): array
    {
        $locale = Locale::currentLocale();
        $collator = new \Collator($locale);
        usort($items, function ($item1, $item2) use ($collator, $property) {
            $comparison = $collator->compare($item1->$property, $item2->$property);

            if ($comparison === false) {
                $comparison = 0;
            }

            return $comparison;
        });

        return $items;
    }
}
