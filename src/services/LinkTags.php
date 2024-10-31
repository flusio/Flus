<?php

namespace App\services;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkTags
{
    public const TAG_REGEX = '/(?:^|[^\pL\pN_])#(?P<tag>[\pL\pN_]+)/u';

    public static function refresh(models\Link $link): void
    {
        $messages = $link->messages();

        $tags = [];

        foreach ($messages as $message) {
            $message_tags = self::extractTags($message->content);
            $tags = array_merge($tags, $message_tags);
        }

        $link->setTags($tags);
        $link->save();
    }

    /**
     * @return string[]
     */
    public static function extractTags(string $content): array
    {
        $result = preg_match_all(self::TAG_REGEX, $content, $matches);

        if ($result === false) {
            return [];
        }

        return $matches['tag'];
    }
}
