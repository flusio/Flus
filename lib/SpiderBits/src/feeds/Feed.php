<?php

namespace SpiderBits\feeds;

/**
 * A Feed is a generic object to abstract Atom and RSS feeds.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feed
{
    /** @var string */
    public $type = '';

    /** @var string */
    public $title = '';

    /** @var string */
    public $description = '';

    /** @var string */
    public $link = '';

    /** @var string[] */
    public $links = [];

    /** @var string[] */
    public $categories = [];

    /** @var \SpiderBits\feeds\Entry[] */
    public $entries = [];

    /**
     * Return a new Feed object from text.
     *
     * @param string $feed_as_string
     *
     * @throws \DomainException if the string cannot be parsed.
     *
     * @return \SpiderBits\feeds\Feed
     */
    public static function fromText($feed_as_string)
    {
        $feed_as_string = trim($feed_as_string);
        if (!$feed_as_string) {
            throw new \DomainException('The string must not be empty.');
        }

        $dom_document = new \DOMDocument();
        $result = @$dom_document->loadXML($feed_as_string);
        if (!$result) {
            // It might be an encoding issue. We try to recover by re-encoding
            // the string with the declared encoding, or UTF-8. It will most
            // probably generate a string with characters replaced by `?`, but
            // at least it will be parsable.
            $result = preg_match(
                '/<?xml\s+(?:(?:.*?)\s)?encoding="(.+?)"/i',
                $feed_as_string,
                $matches
            );
            if ($result) {
                $encoding = $matches[1];
            } else {
                $encoding = 'UTF-8';
            }
            $feed_as_string = mb_convert_encoding($feed_as_string, $encoding, $encoding);
            $result = @$dom_document->loadXML($feed_as_string);

            if (!$result) {
                throw new \DomainException('Can’t parse the given string.');
            }
        }

        if (AtomParser::canHandle($dom_document)) {
            return AtomParser::parse($dom_document);
        }

        if (RssParser::canHandle($dom_document)) {
            return RssParser::parse($dom_document);
        }

        if (RdfParser::canHandle($dom_document)) {
            return RdfParser::parse($dom_document);
        }

        throw new \DomainException('Given string is not a supported standard.');
    }

    /**
     * Returns whether a string is a valid feed.
     *
     * @param string $feed_as_string
     *
     * @return boolean
     */
    public static function isFeed($feed_as_string)
    {
        try {
            self::fromText($feed_as_string);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return whether a string is a valid feed content type.
     *
     * @param string $content_type
     *
     * @return boolean
     */
    public static function isFeedContentType($content_type)
    {
        return (
            strpos($content_type, 'application/atom+xml') !== false ||
            strpos($content_type, 'application/rss+xml') !== false ||
            strpos($content_type, 'application/x-rss+xml') !== false ||
            strpos($content_type, 'application/rdf+xml') !== false ||
            strpos($content_type, 'application/xml') !== false ||
            strpos($content_type, 'text/rss+xml') !== false ||
            strpos($content_type, 'text/xml') !== false ||
            strpos($content_type, 'text/plain') !== false
        );
    }

    /**
     * Return a unique hash of the given feed.
     *
     * @return string
     */
    public function hash()
    {
        return hash('sha256', serialize([
            $this->title,
            $this->description,
            $this->link,
            $this->links,
            $this->categories,
            $this->entries,
        ]));
    }
}
