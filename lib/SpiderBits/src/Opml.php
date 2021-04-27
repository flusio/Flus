<?php

namespace SpiderBits;

/**
 * This class enables to parse OPML content.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml
{
    /** @var array */
    public $outlines = [];

    /**
     * Return a new Opml object from text.
     *
     * @param string $opml_as_string
     *
     * @throws \DomainException if the string cannot be parsed.
     *
     * @return \SpiderBits\Opml
     */
    public static function fromText($feed_as_string)
    {
        $dom_document = new \DOMDocument();
        $result = @$dom_document->loadXML(trim($feed_as_string));
        if (!$result) {
            throw new \DomainException('Can’t parse the given string.');
        }

        if (!self::canHandle($dom_document)) {
            throw new \DomainException('Given string is not OPML.');
        }

        $opml = new Opml();

        foreach ($dom_document->getElementsByTagName('body')->item(0)->childNodes as $node) {
            if (!($node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            if ($node->tagName !== 'outline') {
                continue;
            }

            $opml->outlines[] = self::parseOutline($node);
        }

        return $opml;
    }

    /**
     * Return whether a DOMDocument can be parsed as OPML or not.
     *
     * @param \DOMDocument $dom_document
     *
     * @return boolean
     */
    public static function canHandle($dom_document)
    {
        return (
            $dom_document->documentElement->tagName === 'opml' &&
            $dom_document->getElementsByTagName('head')->count() === 1 &&
            $dom_document->getElementsByTagName('body')->count() === 1
        );
    }

    /**
     * Parse a DOMElement as an OPML outline.
     *
     * It returns an array containing its attributes, plus a `outlines` entry
     * containing its children outlines if any.
     *
     * @param \DOMElement $dom_element
     *
     * @return array
     */
    public static function parseOutline($dom_element)
    {
        $outline = [];

        foreach ($dom_element->attributes as $attribute_name => $attribute) {
            $outline[$attribute_name] = $attribute->value;
        }

        $outline_nodes = $dom_element->getElementsByTagName('outline');
        $outline['outlines'] = [];
        foreach ($outline_nodes as $outline_node) {
            if (!($outline_node instanceof \DOMElement)) {
                continue; // @codeCoverageIgnore
            }

            $outline['outlines'][] = self::parseOutline($outline_node);
        }

        return $outline;
    }
}
