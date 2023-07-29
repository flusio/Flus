<?php

namespace SpiderBits\feeds;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class RssParser
{
    /**
     * Return whether a DOMDocument can be parsed as a RSS feed or not.
     */
    public static function canHandle(\DOMDocument $dom_document): bool
    {
        return (
            $dom_document->documentElement &&
            $dom_document->documentElement->tagName === 'rss'
        );
    }

    /**
     * Parse a DOMDocument as a RSS feed.
     */
    public static function parse(\DOMDocument $dom_document): Feed
    {
        $feed = new Feed();
        $feed->type = 'rss';

        $rss_node = $dom_document->documentElement;

        if (!$rss_node) {
            throw new \Exception('Can’t read the DOMDocument');
        }

        $channel_node = $rss_node->getElementsByTagName('channel')->item(0);
        if (!$channel_node) {
            return $feed;
        }

        foreach ($channel_node->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            $tagName = $node->tagName;
            $value = $node->nodeValue ?: '';

            if ($tagName === 'title') {
                $feed->title = trim(htmlspecialchars_decode($value, ENT_QUOTES));
            }

            if ($tagName === 'description') {
                $feed->description = $value;
            }

            if ($tagName === 'link') {
                $feed->link = $value;
                $feed->links['alternate'] = $value;
            }

            if ($tagName === 'atom:link') {
                $rel = $node->getAttribute('rel');
                if (!$rel) {
                    $rel = 'alternate';
                }

                $href = $node->getAttribute('href');
                $feed->links[$rel] = $href;
            }

            if ($tagName === 'category') {
                $category = $value;
                $feed->categories[$category] = $category;
            }

            if ($tagName === 'item') {
                $entry = self::parseEntry($node);
                $feed->entries[] = $entry;
            }
        }

        return $feed;
    }

    /**
     * Parse a DOMElement as a RSS item.
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

            if ($tagName === 'guid') {
                $entry->id = $value;
            }

            if (
                !$entry->published_at && (
                    $tagName === 'pubDate' ||
                    $tagName === 'dc:date' ||
                    $tagName === 'dc:created'
                )
            ) {
                $published_at = Date::parse($value);
                if ($published_at) {
                    $entry->published_at = $published_at;
                }
            }

            if ($tagName === 'link') {
                $entry->link = $value;
                $entry->links['alternate'] = $value;
            }

            if ($tagName === 'comments') {
                $entry->links['replies'] = $value;
            }

            if ($tagName === 'source') {
                $entry->links['via'] = $node->getAttribute('url');
            }

            if ($tagName === 'atom:link') {
                $rel = $node->getAttribute('rel');
                if (!$rel) {
                    $rel = 'alternate';
                }

                $href = $node->getAttribute('href');
                $entry->links[$rel] = $href;
            }

            if ($tagName === 'category') {
                $category = $value;
                $entry->categories[$category] = $category;
            }

            if ($tagName === 'description' && !$entry->content) {
                $entry->content = $value;
                $entry->content_type = 'html';
            }

            if ($tagName === 'content:encoded') {
                $entry->content = $value;
                $entry->content_type = 'html';
            }
        }

        return $entry;
    }
}
