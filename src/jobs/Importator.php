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
class Importator extends Job
{
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

                $this->importPocketItems($user, $items);

                $offset = $offset + $count;
                $exit_loop = count($items) !== $count;
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
     */
    public function importPocketItems($user, $items)
    {
        $bookmarks_collection = $user->bookmarks();
        $pocket_collection = models\Collection::findOrCreateBy([
            'name' => _('Pocket links'),
            'user_id' => $user->id,
        ], [
            'description' => _('All your links imported from Pocket.'),
        ]);
        $favorite_collection = models\Collection::findOrCreateBy([
            'name' => _('Pocket favorite'),
            'user_id' => $user->id,
        ], [
            'description' => _('All your favorites imported from Pocket.'),
        ]);

        // This will be used to check if URL has already been added by the user
        $link_ids_by_urls = models\Link::daoCall('listIdsByUrls', $user->id);

        // This will store the items that we effectively need to create. We
        // don't create links and their collections-relation on the fly because
        // it would be too intensive. We rather prefer to insert them all at
        // once (see calls to `bulkInsert` below).
        $links_columns = [];
        $links_to_create = [];
        $links_to_collections_to_create = [];

        foreach ($items as $item) {
            $collection_ids = [];
            $collection_ids[] = $pocket_collection->id;
            if ($item['favorite'] === '1') {
                $collection_ids[] = $favorite_collection->id;
            }
            if ($item['status'] === '0') { // 1 means archived, so 0 is "to read"
                $collection_ids[] = $bookmarks_collection->id;
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
                $link->created_at = \Minz\Time::now();

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

                $link_id = $link->id;
            }

            // We now have a link_id and a list of collection ids. We store
            // here the relations in the second _to_create array.
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

        if ($links_to_collections_to_create) {
            $links_to_collections_dao = new models\dao\LinksToCollections();
            $links_to_collections_dao->bulkInsert(
                ['link_id', 'collection_id'],
                $links_to_collections_to_create
            );
        }
    }
}
