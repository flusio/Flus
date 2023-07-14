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
     */
    public static function title(Dom $dom): string
    {
        $xpath_queries = [
            // Look for OpenGraph title first
            '/html/head/meta[@property = "og:title"][1]/attribute::content',
            // Then Twitter meta tag
            '/html/head/meta[@name = "twitter:title"][1]/attribute::content',
            // Still nothing? Look for a <title> tag
            '/html/head/title[1]',

            // Err, still nothing! Let's try to be more tolerant (e.g. Youtube
            // puts the meta and title tags in the body :/)
            '//meta[@property = "og:title"][1]/attribute::content',
            '//meta[@name = "twitter:title"][1]/attribute::content',
            // For titles, we must be sure to not consider svg title tags!
            '//title[not(ancestor::svg)][1]',
        ];

        foreach ($xpath_queries as $query) {
            $title = $dom->select($query);
            if ($title) {
                return $title->text();
            }
        }

        // It's hopeless...
        return '';
    }

    /**
     * Return the description of the DOM document.
     */
    public static function description(Dom $dom): string
    {
        $xpath_queries = [
            // Look for OpenGraph first
            '/html/head/meta[@property = "og:description"][1]/attribute::content',
            // Then Twitter meta tag
            '/html/head/meta[@name = "twitter:description"][1]/attribute::content',
            // Still nothing? Look for a description meta tag
            '/html/head/meta[@name = "description"][1]/attribute::content',

            // Err, still nothing! Let's try to be more tolerant (e.g. Youtube
            // puts the meta tags in the body :/)
            '//meta[@property = "og:description"][1]/attribute::content',
            '//meta[@name = "twitter:description"][1]/attribute::content',
            '//meta[@name = "description"][1]/attribute::content',
        ];

        foreach ($xpath_queries as $query) {
            $description = $dom->select($query);
            if ($description) {
                return $description->text();
            }
        }

        // It's hopeless...
        return '';
    }

    /**
     * Return the illustration URL of the DOM document.
     */
    public static function illustration(Dom $dom): string
    {
        $xpath_queries = [
            // Look for OpenGraph first
            '/html/head/meta[@property = "og:image"][1]/attribute::content',
            // Then Twitter meta tag
            '/html/head/meta[@name = "twitter:image"][1]/attribute::content',
            // Err, still nothing! Let's try to be more tolerant (e.g. Youtube
            // puts the meta tags in the body :/)
            '//meta[@property = "og:image"][1]/attribute::content',
            '//meta[@name = "twitter:image"][1]/attribute::content',
        ];

        foreach ($xpath_queries as $query) {
            $illustration = $dom->select($query);
            if ($illustration) {
                return $illustration->text();
            }
        }

        // It's hopeless...
        return '';
    }

    /**
     * Return the main content of the DOM document.
     */
    public static function content(Dom $dom): string
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

    /**
     * Return the autodiscovered feeds URLs (RSS and Atom).
     *
     * @return string[]
     */
    public static function feeds(Dom $dom): array
    {
        $xpath_query = '//link[@type = "application/rss+xml" or @type = "application/atom+xml"]';

        $nodes = $dom->select($xpath_query);
        if (!$nodes) {
            return [];
        }

        $nodes = $nodes->list();
        if ($nodes === null) {
            return [];
        }

        $feeds = [];

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $feeds[] = $node->getAttribute('href');
            }
        }

        return $feeds;
    }
}
