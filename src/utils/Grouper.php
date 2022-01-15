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
     * @param object $items
     * @param string $property
     */
    public static function groupBy($items, $property)
    {
        $grouped_items = [];
        foreach ($items as $item) {
            $grouped_items[$item->$property][] = $item;
        }

        return $grouped_items;
    }
}
