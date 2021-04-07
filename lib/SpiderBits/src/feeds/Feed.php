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
    public $title = '';

    /** @var string */
    public $description = '';

    /** @var string */
    public $link = '';

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
        $dom_document = new \DOMDocument();
        $result = @$dom_document->loadXML(trim($feed_as_string));
        if (!$result) {
            throw new \DomainException('Can’t parse the given string.');
        }

        if (AtomParser::canHandle($dom_document)) {
            return AtomParser::parse($dom_document);
        }

        if (RssParser::canHandle($dom_document)) {
            return RssParser::parse($dom_document);
        }

        throw new \DomainException('Given string is not a supported standard.');
    }
}
