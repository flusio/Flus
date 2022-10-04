<?php

namespace SpiderBits\feeds;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class HFeedParser
{
    /**
     * Return whether a Dom document can be parsed as an h-feed or not.
     *
     * @param \SpiderBits\Dom $dom
     *
     * @return boolean
     */
    public static function canHandle($dom)
    {
        return $dom->select(self::classSelector('h-feed')) !== null;
    }

    /**
     * Parse a Dom document as an h-feed.
     *
     * @param \SpiderBits\Dom $dom
     *
     * @return \SpiderBits\feeds\Feed
     */
    public static function parse($dom)
    {
        $feed = new Feed();
        $feed->type = 'h-feed';

        $h_feed = $dom->select(self::classSelector('h-feed'), true);
        $h_entries = $h_feed->select(self::classSelector('h-entry'));

        $h_feed->remove(self::classSelector('h-entry'));

        $title_node = $h_feed->select(self::classSelector('p-name', true));
        if ($title_node) {
            $feed->title = htmlspecialchars_decode($title_node->text(), ENT_QUOTES);
        }

        $description_node = $h_feed->select(self::classSelector('p-summary', true));
        if ($description_node) {
            $feed->description = htmlspecialchars_decode($description_node->text(), ENT_QUOTES);
        }

        $url_node = $h_feed->select(self::classSelector('u-url', true) . '/@href');
        if ($url_node) {
            $href = htmlspecialchars_decode($url_node->text(), ENT_QUOTES);
            if ($href) {
                $feed->links['alternate'] = $href;
                $feed->link = $href;
            }
        }

        foreach ($h_entries->list() as $h_entry_node) {
            $entry_dom = $h_entry_node->ownerDocument;
            $entry_xpath = $h_entry_node->getNodePath();
            $h_entry = new \SpiderBits\Dom($entry_dom, $entry_xpath);
            $entry = self::parseEntry($h_entry);
            $feed->entries[] = $entry;
        }

        return $feed;
    }

    /**
     * Parse a Dom node as an h-entry.
     *
     * @param \SpiderBits\Dom $h_entry
     *
     * @return \flusio\feeds\Entry
     */
    private static function parseEntry($h_entry)
    {
        $entry = new Entry();

        $title_node = $h_entry->select(self::classSelector('p-name', true));
        if ($title_node) {
            $entry->title = htmlspecialchars_decode($title_node->text(), ENT_QUOTES);
        }

        $uid_node = $h_entry->select(self::classSelector('u-uid', true) . '/@href');
        if ($uid_node) {
            $entry->id = htmlspecialchars_decode($uid_node->text(), ENT_QUOTES);
        }

        $url_node = $h_entry->select(self::classSelector('u-url', true) . '/@href');
        if ($url_node) {
            $href = htmlspecialchars_decode($url_node->text(), ENT_QUOTES);
            if ($href) {
                $entry->links['alternate'] = $href;
                $entry->link = $href;
            }
        }

        $published_node = $h_entry->select(self::classSelector('dt-published', true));
        if ($published_node) {
            $datetime_node = $published_node->select('/@datetime');
            if ($datetime_node) {
                $datetime = $datetime_node->text();
            } else {
                $datetime = $published_node->text();
            }

            $published_at = Date::parse($datetime);
            if ($published_at) {
                $entry->published_at = $published_at;
            }
        }

        $content_node = $h_entry->select(self::classSelector('e-content', true));
        if ($content_node) {
            $entry->content_type = 'html';
            $entry->content = $content_node->list()[0]->nodeValue;
        }

        return $entry;
    }

    private static function classSelector($class_name, $first_only = false)
    {
        $xpath = "//*[contains(concat(' ', normalize-space(@class), ' '), ' {$class_name} ')]";
        if ($first_only) {
            $xpath .= '[1]';
        }
        return $xpath;
    }
}
