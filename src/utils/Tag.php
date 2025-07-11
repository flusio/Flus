<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Tag
{
    public const TAG_REGEX = '/(?:^|[^\pL\pN_])#(?P<tag>[\pL\pN_]+)/u';

    /**
     * @return string[]
     */
    public static function extract(string $content): array
    {
        $result = preg_match_all(self::TAG_REGEX, $content, $matches);

        if ($result === false) {
            return [];
        }

        return $matches['tag'];
    }
}
