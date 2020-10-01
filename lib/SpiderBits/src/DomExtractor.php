<?php

namespace SpiderBits;

/**
 * The DOM extractor, pure juice.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class DomExtractor
{
    /**
     * Return the title of the DOM document.
     *
     * @param \SpiderBits\Dom $dom
     *
     * @return string
     */
    public static function title($dom)
    {
        // Get the OpenGraph title if it exists
        $title = $dom->select('/html/head/meta[@property = "og:title"]/attribute::content');

        if (!$title) {
            // No OpenGraph? Maybe Twitter meta tag?
            $title = $dom->select('/html/head/meta[@name = "twitter:title"]/attribute::content');
        }

        if (!$title) {
            // Still nothing? Get the <title> tag, and be strict first
            $title = $dom->select('/html/head/title[1]');
        }

        if (!$title) {
            // Then, if we don't find it for some reasons, be more tolerant.
            // This is particularly useful for Youtube!
            // We must be sure to not consider svg title tags though!
            $title = $dom->select('//title[not(ancestor::svg)][1]');
        }

        if ($title) {
            return $title->text();
        } else {
            return '';
        }
    }

    /**
     * Return the main content of the DOM document.
     *
     * @param \SpiderBits\Dom $dom
     *
     * @return string
     */
    public static function content($dom)
    {
        $body = $dom->select('//body');
        if (!$body) {
            return '';
        }

        $main_node = $body->select('//main');
        if (!$main_node) {
            $main_node = $body->select('//*[@id = "main"]');
        }

        if (!$main_node) {
            $main_node = $body;
        }

        $main_node->remove('//script');

        return $main_node->text();
    }
}
