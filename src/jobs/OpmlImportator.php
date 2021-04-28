<?php

namespace flusio\jobs;

use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * Job that import feeds from an OPML file
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OpmlImportator extends Job
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

        $support_user = models\User::supportUser();
        $user = models\User::find($importation->user_id);

        $opml_filepath = $importation->options()['opml_filepath'];
        $opml_as_string = @file_get_contents($opml_filepath);
        if ($opml_as_string === false) {
            @unlink($opml_filepath);
            $importation->fail('Can’t read the OPML file.');
            $importation->save();
            return;
        }

        try {
            $opml = \SpiderBits\Opml::fromText($opml_as_string);
        } catch (\DomainException $e) {
            @unlink($opml_filepath);
            $importation->fail($e->getMessage());
            $importation->save();
            return;
        }

        $feed_urls = [];
        foreach ($opml->outlines as $outline) {
            $feed_urls = array_merge($feed_urls, $this->loadUrlsFromOutline($outline));
        }

        $collection_ids_by_feed_urls = models\Collection::daoCall('listIdsByFeedUrls', $support_user->id);
        $collections_columns = [];
        $collections_to_create = [];
        $followed_collections_to_create = [];

        foreach ($feed_urls as $feed_url) {
            $feed_url = \SpiderBits\Url::sanitize($feed_url);

            if (isset($collection_ids_by_feed_urls[$feed_url])) {
                $collection_id = $collection_ids_by_feed_urls[$feed_url];
            } else {
                $collection = models\Collection::initFeed($support_user->id, $feed_url);
                $collection->created_at = \Minz\Time::now();

                $db_collection = $collection->toValues();
                $collections_to_create = array_merge(
                    $collections_to_create,
                    array_values($db_collection)
                );

                // we need to remember the order of columns
                if (!$collections_columns) {
                    $collections_columns = array_keys($db_collection);
                }

                $collection_ids_by_feed_urls[$feed_url] = $collection->id;
                $collection_id = $collection->id;
            }

            $followed_collections_to_create[] = $user->id;
            $followed_collections_to_create[] = $collection_id;
            $followed_collections_to_create[] = \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT);
        }

        if ($collections_to_create) {
            models\Collection::daoCall(
                'bulkInsert',
                $collections_columns,
                $collections_to_create
            );
        }

        if ($followed_collections_to_create) {
            $followed_collections_dao = new models\dao\FollowedCollection();
            $followed_collections_dao->bulkInsert(
                ['user_id', 'collection_id', 'created_at'],
                $followed_collections_to_create
            );
        }

        $importation->finish();
        $importation->save();

        @unlink($opml_filepath);

        $feeds_fetcher_job = new FeedsFetcher();
        $feeds_fetcher_job->performLater();
    }

    /**
     * Return the list of xmlUrl of an OPML outline and its children.
     *
     * @param array $outline
     *
     * @return string[]
     */
    private function loadUrlsFromOutline($outline)
    {
        $urls = [];
        if (isset($outline['xmlUrl'])) {
            $urls[] = $outline['xmlUrl'];
        }

        foreach ($outline['outlines'] as $child_outline) {
            $urls = array_merge($urls, $this->loadUrlsFromOutline($child_outline));
        }

        return $urls;
    }
}
