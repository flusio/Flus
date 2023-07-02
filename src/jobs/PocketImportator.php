<?php

namespace flusio\jobs;

use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Job that import Pocket links
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class PocketImportator extends \Minz\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->queue = 'importators';
    }

    /**
     * @param string $importation_id
     */
    public function perform($importation_id)
    {
        $importation = models\Importation::find($importation_id);
        if (!$importation) {
            \Minz\Log::warning("Importation #{$importation_id} no longer exists, skipping it");
            return;
        }

        $user = models\User::find($importation->user_id);
        utils\Locale::setCurrentLocale($user->locale);

        if (!isset(\Minz\Configuration::$application['pocket_consumer_key'])) {
            $importation->fail(
                _('We tried to import from Pocket, but pocket_consumer_key is missing (please contact the support).')
            );
            $importation->save();
            return;
        }

        if (!$user->pocket_access_token) {
            $importation->fail(
                _('We tried to import from Pocket, but you didn’t authorize us to access your Pocket data.')
            );
            $importation->save();
            return;
        }

        $consumer_key = \Minz\Configuration::$application['pocket_consumer_key'];
        $pocket_service = new services\Pocket($consumer_key);

        $offset = 0;
        $count = 500;
        $imported_count = 0;
        $error = '';
        $exit_loop = false;
        while (!$exit_loop) {
            try {
                $items = $pocket_service->retrieve($user->pocket_access_token, [
                    'state' => 'all',
                    'detailType' => 'complete',
                    'sort' => 'newest',
                    'count' => $count,
                    'offset' => $offset,
                ]);
            } catch (services\PocketError $e) {
                $user->pocket_error = $e->getCode();
                $user->save();
                $error = $e->getMessage();
                break;
            }

            try {
                $this->importPocketItems($user, $items, $importation->options());
            } catch (\Exception $e) {
                $error = $e->getMessage();
                $dump_filename = \Minz\Configuration::$data_path . "/dump_importation_{$importation->id}.json";
                $dump = json_encode($items);
                file_put_contents($dump_filename, $dump);
                break;
            }

            $offset = $offset + $count;
            $imported_count = $imported_count + count($items);
            $exit_loop = count($items) !== $count;
        }

        if ($error) {
            $importation->fail($error);
        } else {
            $importation->finish();
        }
        $importation->save();
    }

    /**
     * Import the items by batch for the given user.
     *
     * @see https://getpocket.com/developer/docs/v3/retrieve
     *
     * @param \flusio\models\User $user
     * @param array $items
     * @param array $options
     */
    public function importPocketItems($user, $items, $options)
    {
        $bookmarks_collection = $user->bookmarks();
        $pocket_collection = models\Collection::findOrCreateBy([
            'name' => _('Pocket links'),
            'user_id' => $user->id,
        ], [
            'id' => \Minz\Random::timebased(),
            'description' => _('All your links imported from Pocket.'),
        ]);
        if ($options['import_favorites']) {
            $favorite_collection = models\Collection::findOrCreateBy([
                'name' => _('Pocket favorite'),
                'user_id' => $user->id,
            ], [
                'id' => \Minz\Random::timebased(),
                'description' => _('All your favorites imported from Pocket.'),
            ]);
        }

        // This will be used to check if URL has already been added by the user
        $link_ids_by_urls = models\Link::listUrlsToIdsByUserId($user->id);
        // ... or collection already exists
        $collection_ids_by_names = models\Collection::listNamesToIdsByUserId($user->id);

        // This will store the items that we effectively need to create. We
        // don't create links, collections and their relation on the fly because
        // it would be too intensive. We rather prefer to insert them all at
        // once (see calls to `bulkInsert` below).
        $links_to_create = [];
        $collections_to_create = [];
        $links_to_collections_to_create = [];

        foreach ($items as $item) {
            $collection_ids = [];
            $collection_ids[] = $pocket_collection->id;
            if ($item['favorite'] === '1' && $options['import_favorites']) {
                $collection_ids[] = $favorite_collection->id;
            }
            if ($item['status'] === '0' && $options['import_bookmarks']) { // 1 means archived, so 0 is "to read"
                $collection_ids[] = $bookmarks_collection->id;
            }

            if (isset($item['tags']) && !$options['ignore_tags']) {
                // we want to create a collection per tag
                $tags = array_keys($item['tags']);
                foreach ($tags as $tag) {
                    if (isset($collection_ids_by_names[$tag])) {
                        // a collection named by the current tag already
                        // exists, just pick its id
                        $collection_ids[] = $collection_ids_by_names[$tag];
                    } else {
                        // the collection needs to be created
                        $collection = models\Collection::init($user->id, $tag, '', false);
                        $collection->created_at = \Minz\Time::now();

                        $collections_to_create[] = $collection;

                        // add the collection to the map array to avoid
                        // creating it again next time we find it
                        $collection_ids_by_names[$collection->name] = $collection->id;

                        // and add the collection id to the array which stores
                        // the link collections
                        $collection_ids[] = $collection->id;
                    }
                }
            }

            $given_url = \SpiderBits\Url::sanitize($item['given_url']);
            $resolved_url = \SpiderBits\Url::sanitize($item['resolved_url']);
            if (isset($link_ids_by_urls[$given_url])) {
                $link_id = $link_ids_by_urls[$given_url];
            } elseif (isset($link_ids_by_urls[$resolved_url])) {
                $link_id = $link_ids_by_urls[$resolved_url];
            } else {
                // The user didn't added this link yet, so we'll need to create
                // it. First, initiate a new model
                $link = new models\Link($given_url, $user->id, false);
                if (!empty($item['resolved_title'])) {
                    $link->title = $item['resolved_title'];
                } elseif (!empty($item['given_title'])) {
                    $link->title = $item['given_title'];
                }

                // In normal cases, created_at is set on save() call. Since we
                // add links via the bulkInsert call, we have to set created_at
                // first, or it would fail because of the not-null constraint.
                $link->created_at = \Minz\Time::now();

                $links_to_create[] = $link;

                $link_ids_by_urls[$link->url] = $link->id;
                $link_id = $link->id;
            }

            // If time_added is set (not documented), we use it to set the date
            // of attachment to the collection in order to keep the order from
            // Pocket. It defaults to now.
            $published_at = \Minz\Time::now();
            if (isset($item['time_added'])) {
                $timestamp = intval($item['time_added']);
                if ($timestamp > 0) {
                    $published_at = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
                }
            }

            // We now have a link_id and a list of collection ids. We store
            // here the relations in the last _to_create array.
            foreach ($collection_ids as $collection_id) {
                $link_to_collection = new models\LinkToCollection($link_id, $collection_id);
                $link_to_collection->created_at = $published_at;
                $links_to_collections_to_create[] = $link_to_collection;
            }
        }

        // Finally, let the big import (in DB) begin!
        models\Link::bulkInsert($links_to_create);
        models\Collection::bulkInsert($collections_to_create);
        models\LinkToCollection::bulkInsert($links_to_collections_to_create);

        // Delete the collections if they are empty at the end of the
        // importation.
        $count_pocket_links = models\Link::countByCollectionId($pocket_collection->id);
        if ($count_pocket_links === 0) {
            $pocket_collection->remove();
        }

        if ($options['import_favorites']) {
            $count_favorite_links = models\Link::countByCollectionId($favorite_collection->id);
            if ($count_favorite_links === 0) {
                $favorite_collection->remove();
            }
        }
    }
}
