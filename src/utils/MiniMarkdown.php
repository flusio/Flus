<?php

namespace App\utils;

use App\services;

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
        'h1',
        'h2',
        'h3',
        'h4',
        'h5',
        'h6',
    ];

    public function __construct()
    {
        $this->setSafeMode(true);
        $this->setBreaksEnabled(true);

        $this->InlineTypes['#'][] = 'Tag';

        $this->inlineMarkerList .= '#';
    }

    /**
     * Make sure to block header tag if the line starts with a #tag. Otherwise,
     * it would generate a h1 title.
     *
     * @param array{body: string, indent: int, text: string} $line
     *
     * @return ?array<string, mixed>
     */
    protected function blockHeader($line): ?array
    {
        if (preg_match('/^#+\s+/', $line['text'])) {
            return parent::blockHeader($line);
        } else {
            return null;
        }
    }

    /**
     * @param array{text: string, context: string} $excerpt
     *
     * @return ?array<string, mixed>
     */
    protected function inlineTag(array $excerpt): ?array
    {
        $result = preg_match(services\LinkTags::TAG_REGEX, $excerpt['text'], $matches);

        if ($result) {
            $tag = $matches['tag'];
            $tag_url = \Minz\Url::absoluteFor('links', [
                'q' => "#{$tag}",
            ]);

            return array(
                'extent' => strlen($tag) + 1,
                'element' => array(
                    'name' => 'a',
                    'text' => "#{$tag}",
                    'attributes' => array(
                        'href' => $tag_url,
                    ),
                ),
            );
        } else {
            return null;
        }
    }

    /**
     * @see \Parsedown::element
     *
     * @param mixed[] $element
     */
    protected function element(array $element): string
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
