<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class HtmlSanitizer
{
    public static function sanitizeCollectionDescription(string $description, string $base_url): string
    {
        $html_sanitizer = new \SpiderBits\HtmlSanitizer([
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
            'u' => [],
            'ul' => [],
        ], [
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
        ]);

        $healthy_html = $html_sanitizer->sanitize($description);

        $libxml_options = LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD;
        $dom = \SpiderBits\Dom::fromText("<div>{$healthy_html}</div>", $libxml_options);
        $anchors = $dom->select('//a');

        if ($anchors) {
            foreach ($anchors->list() as $node) {
                if (!($node instanceof \DOMElement)) {
                    continue;
                }

                if ($node->hasAttribute('href')) {
                    // Absolutize the URL of the href attribute.
                    $href_node = $node->getAttributeNode('href');
                    $url = \SpiderBits\Url::absolutize($href_node->value, $base_url);
                    $href_node->value = \Minz\Template\SimpleTemplateHelpers::protect($url);

                    // Make sure to open the URL in a new tab.
                    $target_node = new \DOMAttr('target', '_blank');
                    $node->appendChild($target_node);

                    $rel_node = new \DOMAttr('rel', 'noopener noreferrer');
                    $node->appendChild($rel_node);
                }
            }
        }

        return $dom->html();
    }
}
