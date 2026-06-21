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
     * Import the feed into the collection.
     */
    public function importForCollection(models\Collection $collection): void
    {
        $link_ids_by_urls = models\Link::listUrlsToIdsByCollectionId($collection->id);

        $links_to_create = [];
        $links_to_collections_to_create = [];

        foreach ($this->feed->entries as $entry) {
            if (!$entry->link) {
                continue;
            }

            $url = \SpiderBits\Url::sanitize($entry->link);

            if (isset($link_ids_by_urls[$url])) {
                // The URL is already associated to the collection, we have
                // nothing more to do.
                continue;
            }

            if ($entry->published_at) {
                $published_at = $entry->published_at;
            } else {
                $published_at = \Minz\Time::now();
            }

            // The URL is not associated to the collection in database yet,
            // so we create a new link.
            $link = new models\Link($url, $collection->user_id, false);
            $entry_title = trim($entry->title);
            if ($entry_title) {
                $link->title = $entry_title;
            }
            $link->created_at = \Minz\Time::now();

            $links_to_create[] = $link;

            $link_ids_by_urls[$link->url] = $link->id;

            $link_to_collection = new models\LinkToCollection($link->id, $collection->id);
            $link_to_collection->created_at = $published_at;

            $links_to_collections_to_create[] = $link_to_collection;
        }

        models\Link::bulkInsert($links_to_create);
        models\LinkToCollection::bulkInsert($links_to_collections_to_create);

        $collection->syncPublicationFrequencyPerYear();
        $collection->save();
    }

    /**
     * Import the feed as read later links.
     */
    public function importReadLater(models\User $user): void
    {
        $links_to_create = [];
        $urls_to_url_statuses_to_create = [];

        foreach ($this->feed->entries as $entry) {
            if (!$entry->link) {
                continue;
            }

            $url = \SpiderBits\Url::sanitize($entry->link);

            if ($entry->published_at) {
                $published_at = $entry->published_at;
            } else {
                $published_at = \Minz\Time::now();
            }

            $link = $user->findOrBuildLink($url);

            if (!$link->isPersisted()) {
                // There is no link with the given URL in database yet and we
                // want to create it.
                $entry_title = trim($entry->title);
                if ($entry_title) {
                    $link->title = $entry_title;
                }
                $link->created_at = \Minz\Time::now();

                $links_to_create[] = $link;
            }

            if (isset($urls_to_url_statuses_to_create[$url])) {
                $url_status = $urls_to_url_statuses_to_create[$url];
            } else {
                $url_status = new models\UrlStatus($user, $url);
                $url_status->created_at = $published_at;
            }

            $url_status->read_later_at = $published_at;
            $urls_to_url_statuses_to_create[$url] = $url_status;
        }

        models\Link::bulkInsert($links_to_create);
        models\UrlStatus::bulkInsert($urls_to_url_statuses_to_create);
    }
}
