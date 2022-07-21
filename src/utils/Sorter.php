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
     * @param object[] $items
     * @param string $property
     */
    public static function localeSort(&$items, $property)
    {
        $locale = Locale::currentLocale();
        $collator = new \Collator($locale);
        usort($items, function ($item1, $item2) use ($collator, $property) {
            return $collator->compare($item1->$property, $item2->$property);
        });
    }
}
