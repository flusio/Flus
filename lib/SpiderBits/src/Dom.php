<?php

namespace SpiderBits;

/**
 * Searching in a DOM should not be complicated
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Dom
{
    private \DOMDocument $dom;

    private ?string $xpath_query = null;

    /** @var ?\DOMNodeList<\DOMNode> */
    private ?\DOMNodeList $nodes_selected = null;

    /**
     * Return a new Dom object from text.
     *
     * @param non-empty-string $html_as_string
     */
    public static function fromText(string $html_as_string, int $libxml_options = 0): self
    {
        $dom = new \DOMDocument();

        // Encode special chars (all chars above >0x80, i.e. non-ascii
        // chars) to HTML entities.
        $html_as_string = mb_encode_numericentity(
            $html_as_string,
            [0x80, 0x10FFFF, 0, -1],
            'UTF-8'
        );

        @$dom->loadHTML($html_as_string, $libxml_options);

        return new self($dom);
    }

    /**
     * @throws \DomainException if the xpath is invalid or matches no nodes
     */
    public function __construct(\DOMDocument $dom, ?string $xpath_query = null)
    {
        $this->dom = $dom;

        $this->xpath_query = $xpath_query;
        if ($xpath_query) {
            $this->nodes_selected = self::xpathQuery($this->dom, $xpath_query);
        }
    }

    /**
     * Select nodes from the dom based on a XPath query.
     *
     * If the query matches no nodes, the method returns null.
     * Note the XPath query is relative to the current selection (i.e. it's
     * appended).
     */
    public function select(string $xpath_query): ?self
    {
        if ($this->xpath_query) {
            $xpath_query = $this->xpath_query . $xpath_query;
        }

        try {
            return new self($this->dom, $xpath_query);
        } catch (\DomainException $e) {
            return null;
        }
    }

    /**
     * Remove in the current Dom the nodes corresponding to the xpath query
     *
     * Note the XPath query is relative to the current selection (i.e. it's
     * appended).
     */
    public function remove(string $xpath_query): void
    {
        if ($this->xpath_query) {
            $xpath_query = $this->xpath_query . $xpath_query;
        }

        try {
            $nodes_selected = self::xpathQuery($this->dom, $xpath_query);
            foreach ($nodes_selected as $node) {
                if ($node->parentNode) {
                    $node->parentNode->removeChild($node);
                }
            }
        } catch (\DomainException $e) {
        }
    }

    /**
     * Return the selected nodes.
     *
     * @return \DOMNodeList<\DOMNode>|array{}
     */
    public function list(): mixed
    {
        if ($this->nodes_selected) {
            return $this->nodes_selected;
        } else {
            return [];
        }
    }

    /**
     * Return the HTML of the current selected node(s) as a string
     */
    public function html(): string
    {
        if ($this->nodes_selected) {
            $htmls = [];
            foreach ($this->nodes_selected as $node) {
                $htmls[] = $this->dom->saveHTML($node);
            }
            return implode("\n", $htmls);
        } else {
            $html = $this->dom->saveHTML();

            if ($html === false) {
                $html = '';
            }

            return $html;
        }
    }

    /**
     * Return the content of the current selected node(s) as a string
     */
    public function text(): string
    {
        if ($this->nodes_selected) {
            $texts = [];
            foreach ($this->nodes_selected as $node) {
                $texts[] = trim($node->textContent);
            }
            return implode("\n", $texts);
        } else {
            return trim($this->dom->textContent);
        }
    }

    /**
     * Return a list of nodes corresponding to a xpath query
     *
     * @throws \DomainException if the xpath is invalid or matches no nodes
     *
     * @return \DOMNodeList<\DOMNode>
     */
    private static function xpathQuery(\DOMDocument $dom, string $xpath_query): \DOMNodeList
    {
        $dom_xpath = new \DomXPath($dom);
        $nodes_selected = @$dom_xpath->query($xpath_query);

        if ($nodes_selected === false) {
            throw new \DomainException('XPath query is invalid');
        }

        if ($nodes_selected->length === 0) {
            throw new \DomainException('XPath query matches no nodes');
        }

        return $nodes_selected;
    }
}
