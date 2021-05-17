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
     *
     * @param \DOMDocument $dom_document
     *
     * @return boolean
     */
    public static function canHandle($dom_document)
    {
        return $dom_document->documentElement->tagName === 'feed';
    }

    /**
     * Parse a DOMDocument as an Atom feed.
     *
     * @param \DOMDocument $dom_document
     *
     * @return \SpiderBits\feeds\Feed
     */
    public static function parse($dom_document)
    {
        $feed = new Feed();

        foreach ($dom_document->documentElement->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            if ($node->tagName === 'title') {
                $feed->title = trim(htmlspecialchars_decode($node->nodeValue, ENT_QUOTES));
            }

            if ($node->tagName === 'subtitle') {
                $feed->description = trim(htmlspecialchars_decode($node->nodeValue, ENT_QUOTES));
            }

            $rel = $node->getAttribute('rel');
            if (
                $node->tagName === 'link' &&
                ($rel === '' || $rel === 'alternate') &&
                !$feed->link
            ) {
                $feed->link = $node->getAttribute('href');
            }

            if ($node->tagName === 'entry') {
                $entry = self::parseEntry($node);
                $feed->entries[] = $entry;
            }
        }

        return $feed;
    }

    /**
     * Parse a DOMElement as an Atom entry.
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

            if ($node->tagName === 'id') {
                $entry->id = $node->nodeValue;
            }

            if (
                $node->tagName === 'published' ||
                ($node->tagName === 'updated' && !$entry->published_at)
            ) {
                $published_at = \DateTime::createFromFormat(
                    \DateTimeInterface::ATOM,
                    $node->nodeValue
                );
                if ($published_at) {
                    $entry->published_at = $published_at;
                }
            }

            $rel = $node->getAttribute('rel');
            if (
                $node->tagName === 'link' &&
                ($rel === '' || $rel === 'alternate') &&
                !$entry->link
            ) {
                $entry->link = $node->getAttribute('href');
            }
        }

        return $entry;
    }
}
