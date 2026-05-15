<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sorter
{
    /**
     * Sort items by the given property, based on the current locale.
     *
     * $property_or_callable can either be:
     *
     * - a string, in which case it must be the name of a string property in the items' class;
     * - a callable taking an item as a parameter and must return the value to compare.
     *
     * @template T of object
     *
     * @param T[] $items
     * @param string|callable(T): string $property_or_callable
     *
     * @return T[]
     */
    public static function localeSort(array $items, string|callable $property_or_callable): array
    {
        $locale = Locale::currentLocale();
        $collator = new \Collator($locale);
        usort($items, function ($item1, $item2) use ($collator, $property_or_callable) {
            if (is_callable($property_or_callable)) {
                $value1 = $property_or_callable($item1);
                $value2 = $property_or_callable($item2);
            } else {
                $value1 = $item1->$property_or_callable;
                $value2 = $item2->$property_or_callable;
            }

            $comparison = $collator->compare($value1, $value2);

            if ($comparison === false) {
                $comparison = 0;
            }

            return $comparison;
        });

        return $items;
    }
}
