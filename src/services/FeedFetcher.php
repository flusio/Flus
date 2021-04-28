<?php

namespace flusio\services;

use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedFetcher
{
    /** @var \SpiderBits\Cache */
    private $cache;

    /** @var \SpiderBits\Http */
    private $http;

    /** @var boolean */
    private $no_cache;

    /**
     * @param boolean $no_cache Indicates if FeedFetcher should ignore the cache
     * @param boolean $timeout Timeout of GET requests (default is 20)
     */
    public function __construct($no_cache = false, $timeout = 20)
    {
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);

        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->timeout = $timeout;

        $this->no_cache = $no_cache;
    }

    /**
     * Fetch a feed collection
     *
     * @param \flusio\models\Collection $collection
     */
    public function fetch($collection)
    {
        $info = $this->fetchUrl($collection->feed_url);

        $collection->feed_fetched_at = \Minz\Time::now();
        $collection->feed_fetched_code = $info['status'];
        $collection->feed_fetched_error = '';
        if (isset($info['error'])) {
            $collection->feed_fetched_error = $info['error'];
            $collection->save();
            return;
        }

        $feed = $info['feed'];

        $title = substr(trim($feed->title), 0, models\Collection::NAME_MAX_LENGTH);
        if ($title) {
            $collection->name = $title;
        }

        $description = trim($feed->description);
        if ($description) {
            $collection->description = $description;
        }

        $feed_site_url = \SpiderBits\Url::sanitize($feed->link);
        if ($feed_site_url) {
            $collection->feed_site_url = $feed_site_url;
        }

        $collection->save();

        $user_id = $collection->user_id;
        $link_ids_by_urls = models\Link::daoCall('listIdsByUrls', $user_id);
        $link_ids_to_sync = models\Link::daoCall('listIdsToFeedSync', $user_id);

        $links_columns = [];
        $links_to_create = [];
        $links_to_collections_to_create = [];

        foreach ($feed->entries as $entry) {
            $url = \SpiderBits\Url::sanitize($entry->link);

            if ($entry->published_at) {
                $feed_published_at = $entry->published_at;
            } else {
                $feed_published_at = \Minz\Time::now();
            }

            if (isset($link_ids_by_urls[$url])) {
                $link_id = $link_ids_by_urls[$url];
            } else {
                $link = models\Link::init($url, $user_id, false);
                $entry_title = trim($entry->title);
                if ($entry_title) {
                    $link->title = $entry_title;
                }
                $link->created_at = \Minz\Time::now();
                $link->feed_entry_id = $entry->id;
                $link->feed_published_at = $feed_published_at;

                $db_link = $link->toValues();
                $links_to_create = array_merge(
                    $links_to_create,
                    array_values($db_link)
                );
                if (!$links_columns) {
                    $links_columns = array_keys($db_link);
                }

                $link_ids_by_urls[$link->url] = $link->id;
                $link_id = $link->id;
            }

            if (isset($link_ids_to_sync[$link_id])) {
                // This can happen if the URL already exists but wasn't added
                // via a feed sync (i.e. feed_published_at is null). In this
                // case, we want to sync its publication date to get correct
                // order. We don’t do bulk update because it’s complicated.
                // Hopefully, it doesn’t happen often: max once per link and
                // probably less since most of the links are added via the
                // feeds sync.
                models\Link::update($link_id, [
                    'feed_entry_id' => $entry->id,
                    'feed_published_at' => $feed_published_at->format(\Minz\Model::DATETIME_FORMAT),
                ]);
            }

            $links_to_collections_to_create[] = $link_id;
            $links_to_collections_to_create[] = $collection->id;
        }

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

    /**
     * Fetch URL content and return information about the feed
     *
     * @param string $url
     *
     * @return array Possible keys are:
     *     - status (always)
     *     - error
     *     - feed
     */
    public function fetchUrl($url)
    {
        // First, we "GET" the URL...
        $url_hash = \SpiderBits\Cache::hash($url);
        $cached_response = $this->cache->get($url_hash, 60 * 60);
        if (!$this->no_cache && $cached_response) {
            // ... via the cache
            $response = \SpiderBits\Response::fromText($cached_response);
        } else {
            // ... or via HTTP
            try {
                $response = $this->http->get($url);
            } catch (\SpiderBits\HttpError $e) {
                return [
                    'status' => 0,
                    'error' => $e->getMessage(),
                ];
            }

            // that we add to cache
            $this->cache->save($url_hash, (string)$response);
        }

        $info = [
            'status' => $response->status,
        ];

        if (!$response->success) {
            $encodings = mb_list_encodings();
            $data = mb_convert_encoding($response->data, 'UTF-8', $encodings);

            // Okay, Houston, we've had a problem here. Return early, there's
            // nothing more to do.
            $info['error'] = $data;
            return $info;
        }

        $content_type = $response->header('content-type');
        if (!\SpiderBits\feeds\Feed::isFeedContentType($content_type)) {
            $info['error'] = "Invalid content type: {$content_type}";
            return $info; // @codeCoverageIgnore
        }

        try {
            $feed = \SpiderBits\feeds\Feed::fromText($response->data);
            $info['feed'] = $feed;
        } catch (\Exception $e) {
            $info['error'] = (string)$e;
        }

        return $info;
    }
}
