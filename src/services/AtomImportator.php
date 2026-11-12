<?php

namespace App\services;

use App\models;
use App\utils;

/**
 * Service to import Atom files.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AtomImportator
{
    private \SpiderBits\feeds\Feed $feed;

    /**
     * @throws AtomImportatorError
     *     If the file cannot be read, or if it cannot be parsed as an Atom
     *     file.
     */
    public function __construct(string $feed_filepath)
    {
        $feed_as_string = @file_get_contents($feed_filepath);

        if ($feed_as_string === false) {
            throw new AtomImportatorError('Can’t read the Atom file.');
        }

        try {
            $feed = \SpiderBits\feeds\Feed::fromText($feed_as_string);
        } catch (\DomainException $e) {
            throw new AtomImportatorError($e->getMessage());
        }

        $this->feed = $feed;
    }

    /**
     * Import the feed into the source.
     */
    public function importForSource(models\Source $source): void
    {
        // Extract the URLs of the entries first, so we only load the existing
        // links of the source that match these entries.
        $entries = [];
        $urls = [];

        foreach ($this->feed->entries as $entry) {
            if (!$entry->link) {
                continue;
            }

            $entries[] = $entry;
            $urls[] = \SpiderBits\Url::sanitize($entry->link);
        }

        $link_ids_by_url_hashes = models\Link::listUrlHashesToIdsBySource($source, $urls);
        $owner = $source->owner();

        // Load the links already owned by the owner of the source (if any),
        // so they are added to the source instead of being duplicated.
        if ($owner) {
            $owned_links_by_url_hashes = models\Link::listByUserAndUrls($owner, $urls);
        } else {
            $owned_links_by_url_hashes = [];
        }

        $links_to_create = [];
        $links_to_lists_to_create = [];

        foreach ($entries as $index => $entry) {
            $url = $urls[$index];
            $url_hash = utils\Belt::hashUrl($url);

            if (isset($link_ids_by_url_hashes[$url_hash])) {
                // The URL is already associated to the source, we have
                // nothing more to do.
                continue;
            }

            if ($entry->published_at) {
                $published_at = $entry->published_at;
            } else {
                $published_at = \Minz\Time::now();
            }

            if (isset($owned_links_by_url_hashes[$url_hash])) {
                // The owner already has a link with this URL, so we reuse it.
                $link = $owned_links_by_url_hashes[$url_hash];
            } else {
                // The URL is not known yet, so we create a new link.
                $link = new models\Link($url, $owner, false);
                $entry_title = trim($entry->title);
                if ($entry_title) {
                    $link->title = $entry_title;
                }
                $link->created_at = \Minz\Time::now();

                $links_to_create[] = $link;
            }

            $link_ids_by_url_hashes[$link->url_hash] = $link->id;
            $owned_links_by_url_hashes[$link->url_hash] = $link;

            $link_to_list = new models\LinkToList($link, $source);
            $link_to_list->created_at = $published_at;

            $links_to_lists_to_create[] = $link_to_list;
        }

        models\Link::bulkInsert($links_to_create);
        models\LinkToList::bulkInsert($links_to_lists_to_create);

        $source->syncPublicationFrequencyPerYear();
        $source->save();
    }

    /**
     * Import the feed as read later links.
     */
    public function importReadLater(models\User $user): void
    {
        // Extract the URLs of the entries first, so we only load the existing
        // links of the user that match these entries.
        $entries = [];
        $urls = [];

        foreach ($this->feed->entries as $entry) {
            if (!$entry->link) {
                continue;
            }

            $entries[] = $entry;
            $urls[] = \SpiderBits\Url::sanitize($entry->link);
        }

        $links_by_url_hashes = models\Link::listByUserAndUrls($user, $urls);

        $links_to_create = [];
        $url_statuses_to_create = [];

        foreach ($entries as $index => $entry) {
            $url = $urls[$index];
            $url_hash = utils\Belt::hashUrl($url);

            if ($entry->published_at) {
                $published_at = $entry->published_at;
            } else {
                $published_at = \Minz\Time::now();
            }

            if (isset($links_by_url_hashes[$url_hash])) {
                // The owner already has a link with this URL, so we reuse it.
                $link = $links_by_url_hashes[$url_hash];
            } else {
                // The URL is not known yet, so we create a new link.
                $link = new models\Link($url, $user, false);
                $entry_title = trim($entry->title);
                if ($entry_title) {
                    $link->title = $entry_title;
                }
                $link->created_at = \Minz\Time::now();

                $links_to_create[] = $link;
                $links_by_url_hashes[$link->url_hash] = $link;
            }

            if (isset($url_statuses_to_create[$link->url_hash])) {
                $url_status = $url_statuses_to_create[$link->url_hash];
            } else {
                $url_status = new models\UrlStatus($user, $link);
                $url_status->created_at = $published_at;
            }

            $url_status->read_later_at = $published_at;
            $url_statuses_to_create[$link->url_hash] = $url_status;
        }

        models\Link::bulkInsert($links_to_create);
        models\UrlStatus::bulkInsert($url_statuses_to_create);
    }
}
