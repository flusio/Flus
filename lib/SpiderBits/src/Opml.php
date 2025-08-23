<?php

namespace SpiderBits;

/**
 * This class enables to parse OPML content.
 *
 * @phpstan-type Outline array<string, mixed>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml
{
    /** @var Outline[] */
    public array $outlines = [];

    /**
     * Return a new Opml object from text.
     *
     * @throws \DomainException if the string cannot be parsed.
     */
    public static function fromText(string $opml_as_string): self
    {
        $opml_as_string = trim($opml_as_string);
        if (!$opml_as_string) {
            throw new \DomainException('The string must not be empty.');
        }

        $dom_document = new \DOMDocument();
        $result = @$dom_document->loadXML($opml_as_string);
        if (!$result) {
            throw new \DomainException('Can’t parse the given string.');
        }

        if (!self::canHandle($dom_document)) {
            throw new \DomainException('Given string is not OPML.');
        }

        /** @var \DOMElement */
        $body = $dom_document->getElementsByTagName('body')->item(0);

        $opml = new Opml();

        foreach ($body->childNodes as $node) {
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
     */
    public static function canHandle(\DOMDocument $dom_document): bool
    {
        return (
            $dom_document->documentElement !== null &&
            $dom_document->documentElement->tagName === 'opml' &&
            $dom_document->getElementsByTagName('body')->count() === 1
        );
    }

    /**
     * Parse a DOMElement as an OPML outline.
     *
     * It returns an array containing its attributes, plus a `outlines` entry
     * containing its children outlines if any.
     *
     * @return Outline
     */
    public static function parseOutline(\DOMElement $dom_element): array
    {
        $outline = [];

        foreach ($dom_element->attributes as $attribute_name => $attribute) {
            if ($attribute instanceof \DOMAttr) {
                $outline[$attribute_name] = $attribute->value;
            }
        }

        $outline_nodes = $dom_element->getElementsByTagName('outline');
        $outline['outlines'] = [];
        foreach ($outline_nodes as $outline_node) {
            $outline['outlines'][] = self::parseOutline($outline_node);
        }

        return $outline;
    }
}
