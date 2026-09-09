<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Tag
{
    /**
     * Find the tags in a text (e.g. the content of a note).
     *
     * A tag is a "#" followed by letters, digits or underscores. It must be
     * at the start of the text, or preceded by a char that is not part of a
     * tag (so that "a#foo" is not a tag). The tag is captured without its
     * "#" in the "tag" group. The regex stops at the first char that cannot
     * be part of a tag: "#foo!" gives "foo".
     *
     * This regex is used to extract the tags of a text, and by MiniMarkdown
     * to transform them into links.
     */
    public const TAG_REGEX = '/(?:^|[^\pL\pN_])#(?P<tag>[\pL\pN_]+)/u';

    /**
     * Check that a whole string is a tag, "#" included.
     *
     * Contrary to TAG_REGEX, nothing can precede or follow the tag: this is
     * used to validate a string that is expected to be a single tag (e.g.
     * "#foo" in a search query or in a URL parameter).
     */
    private const VALID_TAG_REGEX = '/^#[\pL\pN_]+$/u';

    /**
     * Return the tags found in the given content (without their "#").
     *
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

    /**
     * Return whether the given string is a single tag, "#" included.
     */
    public static function isValid(string $tag): bool
    {
        return preg_match(self::VALID_TAG_REGEX, $tag) === 1;
    }
}
