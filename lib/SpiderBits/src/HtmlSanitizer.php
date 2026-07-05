<?php

namespace SpiderBits;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class HtmlSanitizer
{
    public const DEFAULT_ALLOWED_ELEMENTS = [
        'abbr' => [],
        'a' => ['href', 'title'],
        'blockquote' => [],
        'br' => [],
        'caption' => [],
        'code' => [],
        'dd' => [],
        'del' => [],
        'details' => ['open'],
        'div' => [],
        'dl' => [],
        'dt' => [],
        'em' => [],
        'figcaption' => [],
        'figure' => [],
        'h1' => [],
        'h2' => [],
        'h3' => [],
        'h4' => [],
        'h5' => [],
        'h6' => [],
        'hr' => [],
        'img' => ['src', 'alt', 'title'],
        'i' => [],
        'li' => [],
        'ol' => [],
        'pre' => [],
        'p' => [],
        'q' => [],
        'rp' => [],
        'rt' => [],
        'ruby' => [],
        'small' => [],
        'span' => [],
        'strong' => [],
        'sub' => [],
        'summary' => [],
        'sup' => [],
        'table' => [],
        'tbody' => [],
        'td' => [],
        'tfoot' => [],
        'thead' => [],
        'th' => [],
        'tr' => [],
        'u' => [],
        'ul' => [],
    ];

    /** @var array<string, string[]> */
    private array $allowed_elements;

    /** @var string[] */
    private array $blocked_elements;

    /** @var string[] */
    private array $allowed_link_schemes = ['http', 'https', 'mailto', 'tel'];

    /** @var string[] */
    private array $allowed_media_schemes = ['http', 'https', 'data'];

    private \DOMDocument $healthy_dom;

    /**
     * @param ?array<string, string[]> $allowed_elements
     * @param string[] $blocked_elements
     * @param string[] $allowed_link_schemes
     * @param string[] $allowed_media_schemes
     */
    public function __construct(
        ?array $allowed_elements = null,
        array $blocked_elements = [],
        ?array $allowed_link_schemes = null,
        ?array $allowed_media_schemes = null,
    ) {
        if ($allowed_elements === null) {
            $allowed_elements = self::DEFAULT_ALLOWED_ELEMENTS;
        }

        $this->allowed_elements = $allowed_elements;
        $this->blocked_elements = $blocked_elements;

        if ($allowed_link_schemes !== null) {
            $this->allowed_link_schemes = $allowed_link_schemes;
        }

        if ($allowed_media_schemes !== null) {
            $this->allowed_media_schemes = $allowed_media_schemes;
        }
    }

    public function sanitize(string $html_as_string): string
    {
        $this->healthy_dom = new \DOMDocument();

        // Make sure to have a root node
        $html_as_string = "<div>{$html_as_string}</div>";

        // Encode special chars (all chars above >0x80, i.e. non-ascii
        // chars) to HTML entities.
        $html_as_string = mb_encode_numericentity(
            $html_as_string,
            [0x80, 0x10FFFF, 0, -1],
            'UTF-8'
        );

        $dirty_dom = new \DOMDocument();
        $libxml_options = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD;
        @$dirty_dom->loadHTML($html_as_string, $libxml_options);

        if ($dirty_dom->documentElement) {
            // Ignore the root node and immediately iterates over its child
            // nodes.
            foreach ($dirty_dom->documentElement->childNodes as $child_node) {
                $this->sanitizeNode(
                    $this->healthy_dom,
                    $child_node,
                );
            }
        }

        $healthy_html = $this->healthy_dom->saveHTML();

        if (!$healthy_html) {
            $healthy_html = '';
        }

        return $healthy_html;
    }

    private function sanitizeNode(\DOMNode $healthy_parent_node, \DOMNode $dirty_node): void
    {
        if ($dirty_node instanceof \DOMText) {
            // Text nodes are always accepted.
            $healthy_node = new \DOMText($dirty_node->nodeValue ?? '');
            $healthy_parent_node->appendChild($healthy_node);
        } elseif (
            $dirty_node instanceof \DOMElement &&
            in_array($dirty_node->tagName, $this->blocked_elements)
        ) {
            // The tag element is in the blocked list: ignore it and process
            // its children.
            foreach ($dirty_node->childNodes as $dirty_child_node) {
                $this->sanitizeNode($healthy_parent_node, $dirty_child_node);
            }
        } elseif (
            $dirty_node instanceof \DOMElement &&
            isset($this->allowed_elements[$dirty_node->tagName])
        ) {
            // The tag element is in the allowed list: create a similar node in
            // the healthy DOM.
            $healthy_node = $this->healthy_dom->createElement($dirty_node->tagName);

            $allowed_attributes = $this->allowed_elements[$dirty_node->tagName];

            foreach ($dirty_node->attributes as $dirty_attr_node) {
                if (!in_array($dirty_attr_node->name, $allowed_attributes)) {
                    continue;
                }

                if ($this->isLinkAttribute($dirty_node->tagName, $dirty_attr_node->name)) {
                    $dirty_attr_node_value = $this->sanitizeUrl(
                        $dirty_attr_node->value,
                        $this->allowed_link_schemes,
                    );
                } elseif ($this->isMediaAttribute($dirty_node->tagName, $dirty_attr_node->name)) {
                    $dirty_attr_node_value = $this->sanitizeUrl(
                        $dirty_attr_node->value,
                        $this->allowed_media_schemes,
                    );
                } else {
                    $dirty_attr_node_value = $dirty_attr_node->value;
                }

                $healty_attr_node = new \DOMAttr(
                    $dirty_attr_node->name,
                    $dirty_attr_node_value,
                );

                $healthy_node->appendChild($healty_attr_node);
            }

            $healthy_parent_node->appendChild($healthy_node);

            foreach ($dirty_node->childNodes as $dirty_child_node) {
                $this->sanitizeNode($healthy_node, $dirty_child_node);
            }
        }
    }

    /**
     * Return whether the tag attribute relates to a link.
     */
    private function isLinkAttribute(string $tag_name, string $attribute_name): bool
    {
        return $tag_name === 'a' && $attribute_name === 'href';
    }

    /**
     * Return whether the tag attribute relates to a media.
     */
    private function isMediaAttribute(string $tag_name, string $attribute_name): bool
    {
        return $tag_name === 'img' && $attribute_name === 'src';
    }

    /**
     * Sanitize the given URL if it declares a scheme that isn't allowed (e.g. "javascript:").
     *
     * URLs without an explicit scheme (relative URLs, protocol-relative
     * URLs, fragments) are left untouched as they can't trigger a scheme-based
     * attack.
     *
     * @param string[] $allowed_schemes
     */
    public function sanitizeUrl(string $url, array $allowed_schemes): string
    {
        // Browsers strip C0 control chars, spaces and DEL from a URL before
        // parsing its scheme, so we must do the same to detect obfuscated
        // schemes such as "java\tscript:alert(1)" or "\x01javascript:alert(1)".
        $cleaned_url = preg_replace('/[\x00-\x20\x7f]/', '', $url);

        if ($cleaned_url === null || $cleaned_url === '') {
            return '';
        }

        $parsed_url = parse_url($cleaned_url);

        if ($parsed_url === false || !isset($parsed_url['scheme'])) {
            return $url;
        }

        $scheme = mb_strtolower($parsed_url['scheme']);

        if (!in_array($scheme, $allowed_schemes)) {
            return '';
        }

        return $url;
    }
}
