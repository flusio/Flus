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
class PocketImportator extends Job
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

                $this->importPocketItems($user, $items, $importation->options());

                $offset = $offset + $count;
                $imported_count = $imported_count + count($items);
                $exit_loop = count($items) !== $count;
            } catch (services\PocketError $e) {
                $user->pocket_error = $e->getCode();
                $user->save();
                $error = $e->getMessage();
                $exit_loop = true;
            } catch (\Exception $e) {
                $error = (string)$e;
                $exit_loop = true;
            }
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
            'description' => _('All your links imported from Pocket.'),
        ]);
        if ($options['import_favorites']) {
            $favorite_collection = models\Collection::findOrCreateBy([
                'name' => _('Pocket favorite'),
                'user_id' => $user->id,
            ], [
                'description' => _('All your favorites imported from Pocket.'),
            ]);
        }

        // This will be used to check if URL has already been added by the user
        $link_ids_by_urls = models\Link::daoCall('listIdsByUrls', $user->id);
        // ... or collection already exists
        $collection_ids_by_names = models\Collection::daoCall('listIdsByNames', $user->id);

        // This will store the items that we effectively need to create. We
        // don't create links, collections and their relation on the fly because
        // it would be too intensive. We rather prefer to insert them all at
        // once (see calls to `bulkInsert` below).
        $links_columns = [];
        $links_to_create = [];
        $collections_columns = [];
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

                        // export its values and merge them in the values array
                        $db_collection = $collection->toValues();
                        $collections_to_create = array_merge(
                            $collections_to_create,
                            array_values($db_collection)
                        );

                        // we need to remember the order of columns
                        if (!$collections_columns) {
                            $collections_columns = array_keys($db_collection);
                        }

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
                $link = models\Link::init($given_url, $user->id, false);
                if (!empty($item['resolved_title'])) {
                    $link->title = $item['resolved_title'];
                } elseif (!empty($item['given_title'])) {
                    $link->title = $item['given_title'];
                }

                // In normal cases, created_at is set on save() call. Since we
                // add links via the bulkInsert call, we have to set created_at
                // first, or it would fail because of the not-null constraint.
                // If time_added is set (not documented), we use it to set the
                // property in order to keep the order from Pocket. It defaults
                // to "now".
                $created_at = \Minz\Time::now();
                if (isset($item['time_added'])) {
                    $timestamp = intval($item['time_added']);
                    if ($timestamp > 0) {
                        $created_at->setTimestamp($timestamp);
                    }
                }
                $link->created_at = $created_at;

                // Then, add the link properties values to the list
                $db_link = $link->toValues();
                $links_to_create = array_merge(
                    $links_to_create,
                    array_values($db_link)
                );

                // If not done yet, we have to store the columns order. The
                // order don’t change from a link to another, otherwise it
                // would be impossible to use the bulkInsert
                if (!$links_columns) {
                    $links_columns = array_keys($db_link);
                }

                $link_ids_by_urls[$link->url] = $link->id;
                $link_id = $link->id;
            }

            // We now have a link_id and a list of collection ids. We store
            // here the relations in the last _to_create array.
            foreach ($collection_ids as $collection_id) {
                $links_to_collections_to_create[] = $link_id;
                $links_to_collections_to_create[] = $collection_id;
            }
        }

        // Finally, let the big import (in DB) begin!
        if ($links_to_create) {
            models\Link::daoCall(
                'bulkInsert',
                $links_columns,
                $links_to_create
            );
        }

        if ($collections_to_create) {
            models\Collection::daoCall(
                'bulkInsert',
                $collections_columns,
                $collections_to_create
            );
        }

        if ($links_to_collections_to_create) {
            $links_to_collections_dao = new models\dao\LinksToCollections();
            $links_to_collections_dao->bulkInsert(
                ['link_id', 'collection_id'],
                $links_to_collections_to_create
            );
        }

        // Delete the collections if they are empty at the end of the
        // importation.
        $count_pocket_links = models\Link::daoCall('countByCollectionId', $pocket_collection->id, false);
        if ($count_pocket_links === 0) {
            models\Collection::delete($pocket_collection->id);
        }

        if ($options['import_favorites']) {
            $count_favorite_links = models\Link::daoCall('countByCollectionId', $favorite_collection->id, false);
            if ($count_favorite_links === 0) {
                models\Collection::delete($favorite_collection->id);
            }
        }
    }
}
