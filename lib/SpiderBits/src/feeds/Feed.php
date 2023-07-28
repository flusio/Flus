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
    public string $type = '';

    public string $title = '';

    public string $description = '';

    public string $link = '';

    /** @var string[] */
    public array $links = [];

    /** @var string[] */
    public array $categories = [];

    /** @var \SpiderBits\feeds\Entry[] */
    public array $entries = [];

    /**
     * Return a new Feed object from text.
     *
     * @throws \DomainException if the string cannot be parsed.
     */
    public static function fromText(string $feed_as_string): self
    {
        $feed_as_string = trim($feed_as_string);
        if (!$feed_as_string) {
            throw new \DomainException('The string must not be empty.');
        }

        $dom_document = new \DOMDocument();

        $result = @$dom_document->loadXML($feed_as_string);

        if (!$result) {
            throw new \DomainException('Can’t parse the given string.');
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
     */
    public static function isFeed(string $feed_as_string): bool
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
     */
    public static function isFeedContentType(string $content_type): bool
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
     */
    public function hash(): string
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
