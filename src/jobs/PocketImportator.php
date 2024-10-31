<?php

namespace App\jobs;

use App\models;
use App\services;
use App\utils;

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

    public function perform(int $importation_id): void
    {
        $importation = models\Importation::find($importation_id);
        if (!$importation) {
            \Minz\Log::warning("Importation #{$importation_id} no longer exists, skipping it");
            return;
        }

        $user = $importation->user();
        utils\Locale::setCurrentLocale($user->locale);

        if (!isset(\Minz\Configuration::$application['pocket_consumer_key'])) {
            $importation->fail(
                _('We tried to import from Pocket, but pocket_consumer_key is missing (please contact the support).')
            );
            $importation->save();
            return;
        }

        $pocket_account = models\PocketAccount::findBy([
            'user_id' => $user->id,
        ]);

        if (!$pocket_account || !$pocket_account->access_token) {
            $importation->fail(
                _('We tried to import from Pocket, but you didn’t authorize us to access your Pocket data.')
            );
            $importation->save();
            return;
        }

        /** @var string */
        $consumer_key = \Minz\Configuration::$application['pocket_consumer_key'];
        $pocket_service = new services\Pocket($consumer_key);

        $offset = 0;
        $count = 500;
        $imported_count = 0;
        $error = '';
        $exit_loop = false;
        while (!$exit_loop) {
            try {
                $items = $pocket_service->retrieve($pocket_account->access_token, [
                    'state' => 'all',
                    'detailType' => 'complete',
                    'sort' => 'newest',
                    'count' => $count,
                    'offset' => $offset,
                ]);
            } catch (services\PocketError $e) {
                $pocket_account->error = $e->getCode();
                $pocket_account->save();

                $error = $e->getMessage();
                break;
            }

            try {
                $this->importPocketItems($user, $items, $importation->pocketOptions());
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
     * @param array<array<string, mixed>> $items
     * @param array{
     *     'import_bookmarks': bool,
     *     'import_favorites': bool,
     * } $options
     */
    public function importPocketItems(models\User $user, array $items, array $options): void
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
        $messages = [];

        foreach ($items as $item) {
            /** @var string */
            $given_url = $item['given_url'] ?? '';
            $given_url = \SpiderBits\Url::sanitize($given_url);

            if (!\SpiderBits\Url::isValid($given_url)) {
                continue;
            }

            $collection_ids = [];
            $collection_ids[] = $pocket_collection->id;
            if ($item['favorite'] === '1' && $options['import_favorites']) {
                $collection_ids[] = $favorite_collection->id;
            }
            if ($item['status'] === '0' && $options['import_bookmarks']) { // 1 means archived, so 0 is "to read"
                $collection_ids[] = $bookmarks_collection->id;
            }

            $tags = [];

            if (is_array($item['tags'] ?? null)) {
                foreach (array_keys($item['tags']) as $tag) {
                    $tag = str_replace(' ', '_', $tag);
                    $tags[] = $tag;
                }
            }

            /** @var string */
            $resolved_url = $item['resolved_url'] ?? '';
            $resolved_url = \SpiderBits\Url::sanitize($resolved_url);

            if (isset($link_ids_by_urls[$given_url])) {
                $link_id = $link_ids_by_urls[$given_url];
            } elseif (isset($link_ids_by_urls[$resolved_url])) {
                $link_id = $link_ids_by_urls[$resolved_url];
            } else {
                // The user didn't added this link yet, so we'll need to create
                // it. First, initiate a new model
                $link = new models\Link($given_url, $user->id, false);

                if (is_string($item['resolved_title'] ?? null)) {
                    $link->title = $item['resolved_title'];
                } elseif (is_string($item['given_title'] ?? null)) {
                    $link->title = $item['given_title'];
                }

                $link->setTags($tags);

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
            $published_at = null;
            if (isset($item['time_added'])) {
                $timestamp = $item['time_added'];
                if (is_string($timestamp)) {
                    $timestamp = intval($timestamp);
                }

                if (is_int($timestamp) && $timestamp > 0) {
                    $published_at = \DateTimeImmutable::createFromFormat('U', (string) $timestamp);
                }
            }

            if (!$published_at) {
                $published_at = \Minz\Time::now();
            }

            // We now have a link_id and a list of collection ids. We store
            // here the relations in the last _to_create array.
            foreach ($collection_ids as $collection_id) {
                $link_to_collection = new models\LinkToCollection($link_id, $collection_id);
                $link_to_collection->created_at = $published_at;
                $links_to_collections_to_create[] = $link_to_collection;
            }

            // We create a message containing the list of tags if any.
            if ($tags) {
                $formatted_tags = array_map(function ($tag) {
                    return "#{$tag}";
                }, $tags);

                $content = implode(' ', $formatted_tags);

                $message = new models\Message($user->id, $link_id, $content);
                $message->created_at = $published_at;

                $messages[] = $message;
            }
        }

        // Finally, let the big import (in DB) begin!
        models\Link::bulkInsert($links_to_create);
        models\Collection::bulkInsert($collections_to_create);
        models\LinkToCollection::bulkInsert($links_to_collections_to_create);
        models\Message::bulkInsert($messages);

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
