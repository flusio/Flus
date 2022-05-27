<?php

namespace flusio\utils;

/**
 * An extension to the Parsedown library.
 *
 * It renders a very limited set of HTML tags. It generates "safe" HTML by
 * default.
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MiniMarkdown extends \Parsedown
{
    public const ALLOWED_ELEMENTS = [
        'a',
        'blockquote',
        'br',
        'code',
        'del',
        'em',
        'li',
        'ol',
        'p',
        'pre',
        'strong',
        'ul',
    ];

    public function __construct()
    {
        $this->setSafeMode(true)
             ->setBreaksEnabled(true);
    }

    /**
     * @see \Parsedown::element
     */
    protected function element($element)
    {
        $name = $element['name'] ?? '';
        $text = $element['text'] ?? '';
        if (in_array($name, self::ALLOWED_ELEMENTS)) {
            return parent::element($element);
        } elseif ($text) {
            return self::escape($text, true);
        } else {
            return '';
        }
    }
}
