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
    /** @var \DOMDocument */
    private $dom;

    /** @var string */
    private $xpath_query;

    /** @var \DOMNodeList */
    private $nodes_selected;

    /**
     * Return a new Dom object from text.
     *
     * @param string $html_as_string
     *
     * @return \SpiderBits\Dom
     */
    public static function fromText($html_as_string)
    {
        $dom = new \DOMDocument();
        @$dom->loadHTML($html_as_string);
        return new self($dom);
    }

    /**
     * @param \DOMDocument $dom
     * @param string $xpath_query default is null
     *
     * @throw \DomainException if the xpath is invalid or matches no nodes
     */
    public function __construct($dom, $xpath_query = null)
    {
        $this->dom = $dom;

        $this->xpath_query = $xpath_query;
        if ($xpath_query) {
            $dom_xpath = new \DomXPath($dom);
            $nodes_selected = @$dom_xpath->query($xpath_query);

            if ($nodes_selected === false) {
                throw new \DomainException('XPath query is invalid');
            }

            if ($nodes_selected->length === 0) {
                throw new \DomainException('XPath query matches no nodes');
            }

            $this->nodes_selected = $nodes_selected;
        }
    }

    /**
     * Select nodes from the dom based on a XPath query.
     *
     * If the query matches no nodes, the method returns null.
     * Note the XPath query is relative to the current selection (i.e. it's
     * appended).
     *
     * @param $string $xpath_query
     *
     * @return \SpiderBits\Dom|null
     */
    public function select($xpath_query)
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
     * Return the content of the current selected node(s) as a string
     *
     * @return string
     */
    public function text()
    {
        if ($this->nodes_selected) {
            $texts = [];
            foreach ($this->nodes_selected as $node) {
                $texts[] = trim($node->textContent);
            }
            return implode("\n", $texts);
        } else {
            return $this->dom->textContent;
        }
    }
}
