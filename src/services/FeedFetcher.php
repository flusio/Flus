<?php

namespace App\services;

use App\models;
use App\utils;

/**
 * @phpstan-type Options array{
 *     'timeout': int,
 *     'rate_limit': bool,
 *     'cache': bool,
 * }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedFetcher
{
    public const ERROR_RATE_LIMIT = -1;

    private \SpiderBits\Cache $cache;

    private \SpiderBits\Http $http;

    /** @var Options */
    private $options = [
        'timeout' => 20,
        'rate_limit' => true,
        'cache' => true,
    ];

    /**
     * @param array{
     *     'timeout'?: int,
     *     'rate_limit'?: bool,
     *     'cache'?: bool,
     * } $options
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->options, $options);

        /** @var string */
        $cache_path = \Minz\Configuration::$application['cache_path'];
        $this->cache = new \SpiderBits\Cache($cache_path);

        /** @var string */
        $user_agent = \Minz\Configuration::$application['user_agent'];
        $this->http = new \SpiderBits\Http();
        $this->http->user_agent = $user_agent;
        $this->http->timeout = $this->options['timeout'];
    }

    /**
     * Fetch a feed collection
     */
    public function fetch(models\Collection $collection): void
    {
        $feed_url = $collection->feed_url;
        if (!$feed_url) {
            return;
        }

        $info = $this->fetchUrl($feed_url);

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

        if (!isset($info['feed'])) {
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
            $feed_site_url = \SpiderBits\Url::absolutize($feed->link, $feed_url);
            $feed_site_url = \SpiderBits\Url::sanitize($feed_site_url);
        } else {
            $feed_site_url = $feed_url;
        }

        $collection->feed_site_url = $feed_site_url;

        $collection->save();

        $link_ids_by_urls = models\Link::listUrlsToIdsByCollectionId($collection->id);
        $link_urls_by_entry_ids = models\Link::listEntryIdsToUrlsByCollectionId($collection->id);
        $initial_links_count = count($link_ids_by_urls);

        $links_to_create = [];
        $links_to_collections_to_create = [];

        foreach ($feed->entries as $entry) {
            if (!$entry->link) {
                continue;
            }

            $url = \SpiderBits\Url::absolutize($entry->link, $feed_url);
            $url = \SpiderBits\Url::sanitize($url);

            if (!\SpiderBits\Url::isValid($url)) {
                continue;
            }

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
                if ($link_to_collection) {
                    models\LinkToCollection::update($link_to_collection->id, [
                        'created_at' => $published_at,
                    ]);
                }
            } else {
                // The URL is not associated to the collection in database yet,
                // so we create a new link.
                $link = new models\Link($url, $collection->user_id, false);
                $entry_title = trim($entry->title);
                if ($entry_title) {
                    $link->title = $entry_title;
                }
                $link->created_at = \Minz\Time::now();
                $link->feed_entry_id = $feed_entry_id;
                if (isset($entry->links['replies'])) {
                    $url_replies = \SpiderBits\Url::sanitize($entry->links['replies']);
                    if (filter_var($url_replies, FILTER_VALIDATE_URL) !== false) {
                        $link->url_replies = $url_replies;
                    }
                }

                $links_to_create[$link->id] = $link;

                $link_ids_by_urls[$link->url] = $link->id;
                $link_urls_by_entry_ids[$link->feed_entry_id] = [
                    'id' => $link->id,
                    'url' => $link->url,
                ];
                $link_id = $link->id;

                $link_to_collection = new models\LinkToCollection($link_id, $collection->id);
                $link_to_collection->created_at = $published_at;
                $links_to_collections_to_create[] = $link_to_collection;
            }
        }

        // Filter the links to collections to exclude old ones
        $links_to_collections_to_create = $this->filterLinksToCollections(
            $links_to_collections_to_create,
            $initial_links_count
        );

        // Because some links_to_collections may have been excluded, we don't
        // want to create the associated links.
        // First, we get the list of link_ids that should be created.
        $link_ids_to_keep = array_column($links_to_collections_to_create, 'link_id');
        // Then we do an intersection between links_to_create and link_ids_to_keep.
        // links_to_create is already indexed by link ids, and array_flip
        // returns an array where keys are the values of link_ids_to_keep (i.e.
        // link ids). The resting links are those that we want to create, tada!
        $links_to_create = array_intersect_key($links_to_create, array_flip($link_ids_to_keep));

        // We now can insert the links and links_to_collections via a bulkInsert
        models\Link::bulkInsert($links_to_create);
        models\LinkToCollection::bulkInsert($links_to_collections_to_create);

        if (!$collection->image_fetched_at && $collection->feed_site_url) {
            try {
                $response = $this->http->get($collection->feed_site_url);
            } catch (\SpiderBits\HttpError $e) {
                return;
            }

            if (!$response->success) {
                return;
            }

            /** @var string */
            $content_type = $response->header('content-type', '');
            if (!str_contains($content_type, 'text/html')) {
                $collection->image_fetched_at = \Minz\Time::now();
                $collection->save();
                return;
            }

            $data = $response->utf8Data();

            if (!$data) {
                return;
            }

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
     * @return array{
     *     'status': int,
     *     'error'?: string,
     *     'feed'?: \SpiderBits\feeds\Feed,
     * }
     */
    public function fetchUrl(string $url): array
    {
        // First, we get information about rate limit and IP to select to
        // execute the request (for Youtube).
        /** @var string[] */
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

        $data = $response->utf8Data();

        if (!$response->success) {
            // Okay, Houston, we've had a problem here. Return early, there's
            // nothing more to do.
            $info['error'] = $data;
            return $info;
        }

        /** @var string */
        $content_type = $response->header('content-type', '');
        if (!\SpiderBits\feeds\Feed::isFeedContentType($content_type)) {
            $info['error'] = "Invalid content type: {$content_type}";
            return $info; // @codeCoverageIgnore
        }

        if (!$data) {
            $info['error'] = 'Empty content';
            return $info;
        }

        try {
            $feed = \SpiderBits\feeds\Feed::fromText($data);
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
     * has configured retention policy (keep_period or keep_maximum), the list
     * is limited to the recent enough links_to_collections (or until there are
     * enough of them, i.e. links_count is more than feeds_links_keep_minimum)
     * and to a maximum of feeds_links_keep_maximum.
     *
     * Note that the maximum policy doesn't take into account the initial
     * $links_count. Indeed, the database may already contain the maximum of
     * links, but we want to create the newest links (which should be in the
     * feed that we’re processing). The links in database are probably older
     * and will be deleted later by the Cleaner. However, the method will not
     * return more links_to_collections than the policy.
     *
     * @param models\LinkToCollection[] $links_to_collections
     *
     * @return models\LinkToCollection[]
     */
    private function filterLinksToCollections(array $links_to_collections, int $initial_links_count): array
    {
        /** @var int */
        $feeds_links_keep_minimum = \Minz\Configuration::$application['feeds_links_keep_minimum'];
        /** @var int */
        $feeds_links_keep_maximum = \Minz\Configuration::$application['feeds_links_keep_maximum'];
        /** @var int */
        $feeds_links_keep_period = \Minz\Configuration::$application['feeds_links_keep_period'];

        $feeds_keep_links_date = \Minz\Time::ago($feeds_links_keep_period, 'months');

        if ($feeds_links_keep_period === 0 && $feeds_links_keep_maximum === 0) {
            // If no retention policy, we return all the links_to_collections
            return $links_to_collections;
        }

        // sort the links_to_collections by their publication dates (newest first)
        usort($links_to_collections, function ($lc1, $lc2) {
            return $lc2->created_at <=> $lc1->created_at;
        });

        $to_create = [];
        foreach ($links_to_collections as $link_to_collection) {
            $published_at = $link_to_collection->created_at;
            $recent_enough = (
                $feeds_links_keep_period === 0 ||
                $published_at >= $feeds_keep_links_date
            );
            $links_count = $initial_links_count + count($to_create);
            $enough_links = $links_count >= $feeds_links_keep_minimum;
            $too_many_links = (
                $feeds_links_keep_maximum > 0 &&
                count($to_create) >= $feeds_links_keep_maximum
            );
            $should_be_created = ($recent_enough || !$enough_links) && !$too_many_links;

            if ($should_be_created) {
                $to_create[] = $link_to_collection;
            } else {
                // Because the links are sorted by publication date, we know
                // that either the next will also be too old, or we already
                // reached the limit of links. So we can stop here.
                break;
            }
        }

        return $to_create;
    }

    /**
     * Return true if the url is pointing to Youtube
     */
    private function isYoutube(string $url): bool
    {
        $host = utils\Belt::host($url);
        return (
            str_ends_with($host, 'youtube.com') ||
            str_ends_with($host, 'youtu.be')
        );
    }
}
