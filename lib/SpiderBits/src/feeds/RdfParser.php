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
     *
     * @param \DOMDocument $dom_document
     *
     * @return boolean
     */
    public static function canHandle($dom_document)
    {
        return strpos($dom_document->documentElement->tagName, 'rdf') !== false;
    }

    /**
     * Parse a DOMDocument as a RDF feed.
     *
     * @param \DOMDocument $dom_document
     *
     * @return \SpiderBits\feeds\Feed
     */
    public static function parse($dom_document)
    {
        $feed = new Feed();

        $rss_node = $dom_document->documentElement;
        $channel_node = $rss_node->getElementsByTagName('channel')->item(0);
        if (!$channel_node) {
            return $feed;
        }

        foreach ($channel_node->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            if ($node->tagName === 'title') {
                $feed->title = trim(htmlspecialchars_decode($node->nodeValue, ENT_QUOTES));
            }

            if ($node->tagName === 'description') {
                $feed->description = trim(htmlspecialchars_decode($node->nodeValue, ENT_QUOTES));
            }

            if ($node->tagName === 'link') {
                $feed->link = $node->nodeValue;
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
     *
     * @param \DOMElement $dom_element
     *
     * @return \flusio\feeds\Entry
     */
    private static function parseEntry($dom_element)
    {
        $entry = new Entry();

        foreach ($dom_element->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            if ($node->tagName === 'title') {
                $entry->title = trim(htmlspecialchars_decode($node->nodeValue, ENT_QUOTES));
            }

            if ($node->tagName === 'dc:date') {
                $published_at = \DateTime::createFromFormat(
                    \DateTimeInterface::ATOM,
                    $node->nodeValue
                );
                if ($published_at) {
                    $entry->published_at = $published_at;
                }
            }

            if ($node->tagName === 'link') {
                $entry->link = $node->nodeValue;
                $entry->id = $node->nodeValue;
            }
        }

        return $entry;
    }
}
