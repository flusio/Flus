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
     * Return the duration in minutes of the element in the DOM.
     *
     * If the DOM declares an element with itemprop="duration", return the
     * corresponding duration. Otherwise, estimate the duration from the
     * content itself by counting the number of words in the content and divide
     * by an average reading speed (i.e. 200 words per minute).
     */
    public static function duration(Dom $dom): int
    {
        // Search for a node having the attribute itemprop="duration
        // @see https://schema.org/docs/gs.html
        $duration_node = $dom->select('//*[@itemprop = "duration"]/attribute::content');

        if ($duration_node) {
            try {
                $interval = new \DateInterval($duration_node->text());
                // Convert the interval to minutes
                $duration = $interval->y * 12 * 30 * 24 * 60;
                $duration += $interval->m * 30 * 24 * 60;
                $duration += $interval->d * 24 * 60;
                $duration += $interval->h * 60;
                $duration += $interval->i;
                if ($interval->s >= 30) {
                    $duration += 1;
                }
                return $duration;
            } catch (\Exception $e) {
                // Do nothing and fallback to the other mode
            }
        }

        // If there is no duration node (or if its content can't be parsed),
        // roughly estimate the duration from the DOM content.
        $content = self::content($dom);
        $words = array_filter(explode(' ', $content));
        $average_reading_speed = 200;
        return intval(count($words) / $average_reading_speed);
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
        $feeds = [];

        foreach ($nodes as $node) {
            if ($node instanceof \DOMElement) {
                $feeds[] = $node->getAttribute('href');
            }
        }

        return $feeds;
    }
}
