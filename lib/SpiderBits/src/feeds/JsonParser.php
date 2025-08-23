<?php

namespace SpiderBits\feeds;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class JsonParser
{
    /**
     * Return whether an array can be parsed as a JSON feed or not.
     *
     * @param mixed[] $json_document
     */
    public static function canHandle(array $json_document): bool
    {
        return (
            isset($json_document['version']) &&
            isset($json_document['title']) &&
            isset($json_document['items'])
        );
    }

    /**
     * Parse an array as a JSON feed.
     *
     * @param mixed[] $json_document
     */
    public static function parse(array $json_document): Feed
    {
        $feed = new Feed();
        $feed->type = 'json';

        $feed->title = self::getSecureString($json_document, 'title');

        $description = $json_document['description'] ?? '';
        if (!is_string($description)) {
            $description = '';
        }
        $feed->description = $description;

        $link_url = self::getSecureString($json_document, 'home_page_url');
        if ($link_url) {
            $feed->link = $link_url;
            $feed->links['alternate'] = $link_url;
        }

        $feed_url = self::getSecureString($json_document, 'feed_url');
        if ($feed_url) {
            $feed->links['self'] = $feed_url;
        }

        $items = $json_document['items'] ?? [];
        if (is_array($items)) {
            foreach ($items as $item) {
                if (is_array($item)) {
                    $entry = self::parseEntry($item);
                    $feed->entries[] = $entry;
                }
            }
        }

        return $feed;
    }

    /**
     * Parse an array as a JSON entry.
     *
     * @param mixed[] $json_item
     */
    private static function parseEntry(array $json_item): Entry
    {
        $entry = new Entry();

        $entry->title = self::getSecureString($json_item, 'title');

        $id = $json_item['id'] ?? '';
        if (!is_string($id)) {
            $id = '';
        }
        $entry->id = $id;

        $published_at = Date::parse($json_item['date_published'] ?? '');
        if ($published_at) {
            $entry->published_at = $published_at;
        }

        $link_url = self::getSecureString($json_item, 'url');
        if ($link_url) {
            $entry->link = $link_url;
            $entry->links['alternate'] = $link_url;
        }

        $tags = $json_item['tags'] ?? [];
        foreach ($tags as $tag) {
            if (is_string($tag)) {
                $entry->categories[] = $tag;
            }
        }

        $html_content = $json_item['content_html'] ?? '';
        $text_content = $json_item['content_text'] ?? '';

        if ($html_content) {
            $entry->content_type = 'html';
            $entry->content = $html_content;
        } elseif ($text_content) {
            $entry->content_type = 'text';
            $entry->content = $text_content;
        }

        return $entry;
    }

    /**
     * Return the string value of the given array key. The string is sanitized
     * from HTML content.
     *
     * @param mixed[] $array
     */
    private static function getSecureString(array $array, string $key): string
    {
        $value = $array[$key] ?? '';

        if (!is_string($value)) {
            return '';
        }

        return trim(htmlspecialchars_decode($value, ENT_QUOTES));
    }
}
