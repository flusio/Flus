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
    public const ERROR_RATE_LIMIT = -1;

    /** @var \SpiderBits\Cache */
    private $cache;

    /** @var \SpiderBits\Http */
    private $http;

    /** @var array */
    private $options = [
        'timeout' => 20,
        'rate_limit' => true,
        'cache' => true,
    ];

    /**
     * @param array $options
     *     A list of options where possible keys are:
     *     - timeout (integer)
     *     - rate_limit (boolean)
     *     - cache (boolean)
     */
    public function __construct($options = [])
    {
        $this->options = array_merge($this->options, $options);

        $cache_path = \Minz\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);

        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http->timeout = $this->options['timeout'];
    }

    /**
     * Fetch a feed collection
     *
     * @param \flusio\models\Collection $collection
     */
    public function fetch($collection)
    {
        $info = $this->fetchUrl($collection->feed_url);

        if ($info['status'] === self::ERROR_RATE_LIMIT) {
            // In case of a rate limit error, skip the link so it can be
            // fetched later.
            return;
        }

        $collection->feed_fetched_at = \Minz\Time::now();
        $collection->feed_fetched_code = $info['status'];
        $collection->feed_fetched_error = null;
        if (isset($info['error'])) {
            $collection->feed_fetched_error = $info['error'];
            $collection->save();
            return;
        }

        $feed = $info['feed'];
        $feed_hash = $feed->hash();

        if ($feed_hash === $collection->feed_last_hash) {
            // The feed didn’t change, do nothing
            $collection->save();
            return;
        }

        $collection->feed_last_hash = $feed_hash;
        $collection->feed_type = $feed->type;

        $title = utils\Belt::cut(trim($feed->title), models\Collection::NAME_MAX_LENGTH);
        if ($title) {
            $collection->name = $title;
        }

        $description = trim($feed->description);
        if ($description) {
            $collection->description = $description;
        }

        if ($feed->link) {
            $feed_site_url = \SpiderBits\Url::absolutize($feed->link, $collection->feed_url);
            $feed_site_url = \SpiderBits\Url::sanitize($feed_site_url);
        } else {
            $feed_site_url = $collection->feed_url;
        }

        $collection->feed_site_url = $feed_site_url;

        $collection->save();

        $link_ids_by_urls = models\Link::daoCall('listUrlsToIdsByCollectionId', $collection->id);
        $link_urls_by_entry_ids = models\Link::daoCall('listEntryIdsToUrlsByCollectionId', $collection->id);
        $initial_links_count = count($link_ids_by_urls);

        $links_to_create = [];
        $links_to_collections_to_create = [];

        foreach ($feed->entries as $entry) {
            if (!$entry->link) {
                continue;
            }

            $url = \SpiderBits\Url::absolutize($entry->link, $collection->feed_url);
            $url = \SpiderBits\Url::sanitize($url);

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

            if ($entry->id) {
                $feed_entry_id = $entry->id;
            } else {
                $feed_entry_id = $url;
            }

            if (
                isset($link_urls_by_entry_ids[$feed_entry_id]) &&
                $link_urls_by_entry_ids[$feed_entry_id]['url'] !== $url
            ) {
                // We detected a link with the same entry id has a different
                // URL. This can happen if the URL was changed by the publisher
                // after our first fetch. Normally, there is a redirection on
                // the server so it's not a big deal to not track this change,
                // but it duplicates content.
                // To avoid this problem, we update the link URL and reset the
                // title and fetched_at so the link is resynchronised by the
                // LinkFetcher service.
                $link_id = $link_urls_by_entry_ids[$feed_entry_id]['id'];
                models\Link::update($link_id, [
                    'url' => $url,
                    'title' => $url,
                    'fetched_at' => null,
                ]);

                // we also update the publication date in case it changed
                $link_to_collection = models\LinkToCollection::findBy([
                    'link_id' => $link_id,
                    'collection_id' => $collection->id,
                ]);
                models\LinkToCollection::update($link_to_collection->id, [
                    'created_at' => $published_at->format(\Minz\Model::DATETIME_FORMAT),
                ]);
            } else {
                // The URL is not associated to the collection in database yet,
                // so we create a new link.
                $link = models\Link::init($url, $collection->user_id, false);
                $entry_title = trim($entry->title);
                if ($entry_title) {
                    $link->title = $entry_title;
                }
                $link->created_at = \Minz\Time::now();
                $link->feed_entry_id = $feed_entry_id;

                $links_to_create[] = $link;

                $link_ids_by_urls[$link->url] = $link->id;
                $link_urls_by_entry_ids[$link->url] = $link->feed_entry_id;
                $link_id = $link->id;

                $links_to_collections_to_create[] = new models\LinkToCollection([
                    'created_at' => $published_at,
                    'link_id' => $link_id,
                    'collection_id' => $collection->id,
                ]);
            }
        }

        // Filter the links to collections to exclude old ones
        $links_to_collections_to_create = $this->filterLinksToCollections(
            $links_to_collections_to_create,
            $initial_links_count
        );

        models\Link::bulkInsert($links_to_create);
        models\LinkToCollection::bulkInsert($links_to_collections_to_create);

        if (!$collection->image_fetched_at) {
            try {
                $response = $this->http->get($collection->feed_site_url);
            } catch (\SpiderBits\HttpError $e) {
                return;
            }

            if (!$response->success) {
                return;
            }

            $content_type = $response->header('content-type');
            if (!utils\Belt::contains($content_type, 'text/html')) {
                $collection->image_fetched_at = \Minz\Time::now();
                $collection->save();
                return;
            }

            $encodings = mb_list_encodings();
            $data = mb_convert_encoding($response->data, 'UTF-8', $encodings);

            $dom = \SpiderBits\Dom::fromText($data);
            $url_illustration = \SpiderBits\DomExtractor::illustration($dom);
            $url_illustration = \SpiderBits\Url::sanitize($url_illustration);
            if (!$url_illustration) {
                $collection->image_fetched_at = \Minz\Time::now();
                $collection->save();
                return;
            }

            $image_service = new Image();
            $image_filename = $image_service->generatePreviews($url_illustration);
            $collection->image_filename = $image_filename;
            $collection->image_fetched_at = \Minz\Time::now();
            $collection->save();
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
        // First, we get information about rate limit and IP to select to
        // execute the request (for Youtube).
        $server_ips = \Minz\Configuration::$application['server_ips'];
        if (!$this->options['rate_limit']) {
            // rate limit is disabled for this call
            $is_rate_limited = false;
            $selected_ip = null;
        } elseif ($server_ips && $this->isYoutube($url)) {
            // Youtube has a strict rate limit, so we load balance the requests
            // on different IPs if the admin set the options.
            $is_rate_limited = true;
            $selected_ip = null;
            // shuffle the IPs so it's not always the same IPs that fetch
            // Youtube
            shuffle($server_ips);
            foreach ($server_ips as $server_ip) {
                // we calculate the rate limit for the given IP and if it
                // hasn't been reached, we select the IP to be passed to Curl.
                $is_rate_limited = models\FetchLog::hasReachedRateLimit(
                    $url,
                    'feed',
                    $server_ip
                );
                if (!$is_rate_limited) {
                    $selected_ip = $server_ip;
                    break;
                }
            }
        } else {
            // the default case where we calculate the reach limit and select
            // no interface.
            $is_rate_limited = models\FetchLog::hasReachedRateLimit($url, 'feed');
            $selected_ip = null;
        }

        // First, we "GET" the URL...
        $url_hash = \SpiderBits\Cache::hash($url);
        $cached_response = $this->cache->get($url_hash, 60 * 60);
        if ($this->options['cache'] && $cached_response) {
            // ... via the cache
            $response = \SpiderBits\Response::fromText($cached_response);
        } elseif (!$is_rate_limited) {
            // ... or via HTTP
            $options = [
                'max_size' => 20 * 1024 * 1024,
            ];
            if ($selected_ip) {
                $options['interface'] = $selected_ip;
                models\FetchLog::log($url, 'feed', $selected_ip);
            } else {
                models\FetchLog::log($url, 'feed');
            }

            try {
                $response = $this->http->get($url, [], $options);
            } catch (\SpiderBits\HttpError $e) {
                return [
                    'status' => 0,
                    'error' => $e->getMessage(),
                ];
            }

            // that we add to cache on success
            if ($response->success) {
                $this->cache->save($url_hash, (string)$response);
            }
        } else {
            return [
                'status' => self::ERROR_RATE_LIMIT,
                'error' => 'Reached rate limit',
            ];
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

    /**
     * Filter the links_to_collections list in order to create them.
     *
     * In normal case, it should return the full list. But if the administrator
     * has configured the feeds_links_keep_period, the list is limited to the
     * recent enough links_to_collections (or until there are enough of them,
     * i.e. links_count is more than feeds_links_keep_minimum).
     *
     * Note that in both cases, all the links are already created. Only their
     * relations with collections will not be created. It's not a problem
     * because they will be removed by the Cleaner job during the night.
     *
     * @param array $links_to_collections
     *     All the links_to_collections initialized from the feed.
     * @param integer $links_count
     *     The initial count of collection links.
     *
     * @return array
     */
    private function filterLinksToCollections($links_to_collections, $links_count)
    {
        $feeds_links_keep_minimum = \Minz\Configuration::$application['feeds_links_keep_minimum'];
        $feeds_links_keep_period = \Minz\Configuration::$application['feeds_links_keep_period'];
        $feeds_keep_links_date = \Minz\Time::ago($feeds_links_keep_period, 'months');

        if ($feeds_links_keep_period <= 0) {
            // If "keep period" is not set, we create all the links_to_collections
            return $links_to_collections;
        }

        $to_create = [];

        // sort the links_to_collections by their publication dates
        // (newest first)
        usort($links_to_collections, function ($lc1, $lc2) {
            return $lc2->created_at <=> $lc1->created_at;
        });

        foreach ($links_to_collections as $link_to_collection) {
            $published_at = $link_to_collection->created_at;
            $recent_enough = $published_at >= $feeds_keep_links_date;
            $enough_links = $links_count >= $feeds_links_keep_minimum;

            if ($recent_enough || !$enough_links) {
                $to_create[] = $link_to_collection;
                $links_count = $links_count + 1;
            }
        }

        return $to_create;
    }

    /**
     * Return true if the url is pointing to Youtube
     *
     * @param string $url
     *
     * @return boolean
     */
    private function isYoutube($url)
    {
        $host = utils\Belt::host($url);
        return utils\Belt::endsWith($host, 'youtube.com');
    }
}
