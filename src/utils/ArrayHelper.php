<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ArrayHelper
{
    /**
     * Trim an array of strings.
     *
     * @param string[] $strings
     * @return string[]
     */
    public static function trim(array $strings): array
    {
        return array_map('trim', $strings);
    }
}
