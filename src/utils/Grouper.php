<?php

namespace flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Grouper
{
    /**
     * Group objects by the given property.
     *
     * @template T of object
     *
     * @param T[] $items
     *
     * @return array<string|int, T[]>
     */
    public static function groupBy(array $items, string $property): array
    {
        $grouped_items = [];

        foreach ($items as $item) {
            $grouped_items[$item->$property][] = $item;
        }

        return $grouped_items;
    }
}
