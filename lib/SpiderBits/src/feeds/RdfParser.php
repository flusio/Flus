<?php

namespace SpiderBits\feeds;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class RdfParser
{
    /**
     * Return whether a DOMDocument can be parsed as a RDF feed or not.
     */
    public static function canHandle(\DOMDocument $dom_document): bool
    {
        return (
            $dom_document->documentElement &&
            str_contains($dom_document->documentElement->tagName, 'rdf')
        );
    }

    /**
     * Parse a DOMDocument as a RDF feed.
     */
    public static function parse(\DOMDocument $dom_document): Feed
    {
        $feed = new Feed();
        $feed->type = 'rdf';

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
                $feed->description = trim(htmlspecialchars_decode($value, ENT_QUOTES));
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
        }

        foreach ($rss_node->getElementsByTagName('item') as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            $entry = self::parseEntry($node);
            $feed->entries[] = $entry;
        }

        return $feed;
    }

    /**
     * Parse a DOMElement as a RDF item.
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

            if ($tagName === 'dc:date') {
                $published_at = Date::parse($value);
                if ($published_at) {
                    $entry->published_at = $published_at;
                }
            }

            if ($tagName === 'link') {
                $entry->link = $value;
                $entry->id = $value;
                $entry->links['alternate'] = $value;
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
