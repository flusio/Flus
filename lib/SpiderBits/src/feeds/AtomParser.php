<?php

namespace SpiderBits\feeds;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AtomParser
{
    /**
     * Return whether a DOMDocument can be parsed as an Atom feed or not.
     */
    public static function canHandle(\DOMDocument $dom_document): bool
    {
        return (
            $dom_document->documentElement &&
            $dom_document->documentElement->tagName === 'feed'
        );
    }

    /**
     * Parse a DOMDocument as an Atom feed.
     */
    public static function parse(\DOMDocument $dom_document): Feed
    {
        $feed = new Feed();
        $feed->type = 'atom';

        if (!$dom_document->documentElement) {
            throw new \Exception('Can’t read the DOMDocument');
        }

        foreach ($dom_document->documentElement->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            $tagName = $node->tagName;
            $value = $node->nodeValue ?: '';

            if ($tagName === 'title') {
                $feed->title = trim(htmlspecialchars_decode($value, ENT_QUOTES));
            }

            if ($tagName === 'subtitle') {
                $feed->description = $value;
            }

            if ($tagName === 'link') {
                $rel = $node->getAttribute('rel');
                if (!$rel) {
                    $rel = 'alternate';
                }

                $href = $node->getAttribute('href');
                $feed->links[$rel] = $href;
                if ($rel === 'alternate' && !$feed->link) {
                    $feed->link = $href;
                }
            }

            if ($tagName === 'category') {
                $term = $node->getAttribute('term');
                $label = $node->getAttribute('label');
                if (!$label) {
                    $label = $term;
                }
                $feed->categories[$term] = $label;
            }

            if ($tagName === 'entry') {
                $entry = self::parseEntry($node);
                $feed->entries[] = $entry;
            }
        }

        return $feed;
    }

    /**
     * Parse a DOMElement as an Atom entry.
     */
    private static function parseEntry(\DOMElement $dom_element): Entry
    {
        $entry = new Entry();

        foreach ($dom_element->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            $tagName = $node->tagName;
            $value = $node->nodeValue ?: '';

            if ($tagName === 'title') {
                $entry->title = trim(htmlspecialchars_decode($value, ENT_QUOTES));
            }

            if ($tagName === 'id') {
                $entry->id = $value;
            }

            if (
                $tagName === 'published' ||
                ($tagName === 'updated' && !$entry->published_at)
            ) {
                $published_at = Date::parse($value);
                if ($published_at) {
                    $entry->published_at = $published_at;
                }
            }

            if ($tagName === 'link') {
                $rel = $node->getAttribute('rel');
                if (!$rel) {
                    $rel = 'alternate';
                }

                $href = $node->getAttribute('href');
                $entry->links[$rel] = $href;
                if ($rel === 'alternate' && !$entry->link) {
                    $entry->link = $href;
                }
            }

            if ($tagName === 'category') {
                $term = $node->getAttribute('term');
                $label = $node->getAttribute('label');
                if (!$label) {
                    $label = $term;
                }
                $entry->categories[$term] = $label;
            }

            if ($tagName === 'content') {
                $type = $node->getAttribute('type');
                if ($type) {
                    $entry->content_type = $type;
                } else {
                    $entry->content_type = 'text';
                }

                $entry->content = $value;
            }
        }

        return $entry;
    }
}
