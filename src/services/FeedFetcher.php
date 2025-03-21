<?php

namespace App\services;

use App\http;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeedFetcher
{
    public const ERROR_RATE_LIMIT = -1;
    public const ERROR_UNKNOWN = 0;

    private http\Fetcher $fetcher;

    /**
     * @param array{
     *     http_timeout?: int,
     *     ignore_cache?: bool,
     *     ignore_rate_limit?: bool,
     * } $options
     */
    public function __construct(array $options = [])
    {
        $this->fetcher = new http\Fetcher(
            http_timeout: $options['http_timeout'] ?? 20,
            cache_duration: 1 * 60 * 60,
            ignore_cache: $options['ignore_cache'] ?? false,
            ignore_rate_limit: $options['ignore_rate_limit'] ?? false,
            headers: [
                'Accept' => 'application/atom+xml,application/rss+xml,application/xml',
            ],
        );
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
        $collection->feed_fetched_next_at = $this->calculateNextFetchedAt($collection);
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
            if (!$entry->link && \SpiderBits\Url::isValid($entry->id)) {
                $entry->link = $entry->id;
            }

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

        $collection->feed_fetched_next_at = $this->calculateNextFetchedAt($collection);
        $collection->save();

        if (!$collection->image_fetched_at && $collection->feed_site_url) {
            try {
                $response = $this->fetcher->get($collection->feed_site_url, type: 'link');
            } catch (http\FetcherError $e) {
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
     * @throws \DomainException if $url is empty
     *
     * @return array{
     *     'status': int,
     *     'error'?: string,
     *     'feed'?: \SpiderBits\feeds\Feed,
     * }
     */
    public function fetchUrl(string $url): array
    {
        if (empty($url)) {
            throw new \DomainException('URL cannot be empty');
        }

        try {
            $response = $this->fetcher->get($url, type: 'feed');
        } catch (http\RateLimitError $e) {
            return [
                'status' => self::ERROR_RATE_LIMIT,
                'error' => 'Reached rate limit',
            ];
        } catch (http\UnexpectedHttpError $e) {
            return [
                'status' => self::ERROR_UNKNOWN,
                'error' => $e->getMessage(),
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
        $feeds_links_keep_minimum = \App\Configuration::$application['feeds_links_keep_minimum'];
        $feeds_links_keep_maximum = \App\Configuration::$application['feeds_links_keep_maximum'];
        $feeds_links_keep_period = \App\Configuration::$application['feeds_links_keep_period'];

        $feeds_keep_links_date = \Minz\Time::ago($feeds_links_keep_period, 'months');

        if ($feeds_links_keep_period === 0 && $feeds_links_keep_maximum === 0) {
            // If no retention policy, we return all the links_to_collections
            return $links_to_collections;
        }

        // sort the links_to_collections by their publication dates (newest first)
        usort($links_to_collections, function ($lc1, $lc2): int {
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
     * Return a new value for the feed's feed_fetched_next_at attribute based
     * on the publication frequency of the feed.
     *
     * The less the feed publishes, the less often it will be synchronized.
     *
     * This minimal value is 1h in the future, and the maximal value is 24h in
     * the future.
     */
    private function calculateNextFetchedAt(models\Collection $feed): \DateTimeImmutable
    {
        $min_minutes = 60;
        $max_minutes = 60 * 24;

        $publication_frequency = $feed->publicationFrequencyPerYear();

        if ($publication_frequency === 0) {
            return \Minz\Time::fromNow($max_minutes, 'minutes');
        }

        // This function approximates the following:
        // - publish 4 times a month = synchronize each 1 hour
        // - publish 2 times a month = synchronize each 6 hours
        // - publish 1 time a month = synchronize each 12 hours
        // - publish 1 time each 2 months = synchronize each 24 hours
        $next_fetched_at_as_minutes = (int) round((180 / $publication_frequency - 3) * 60);

        // Add a bit of randomness
        $random_delta = random_int(-15, 15);
        $next_fetched_at_as_minutes = $next_fetched_at_as_minutes + $random_delta;

        // Bound the value between the min and the max values
        $next_fetched_at_as_minutes = min($max_minutes, $next_fetched_at_as_minutes);
        $next_fetched_at_as_minutes = max($min_minutes, $next_fetched_at_as_minutes);

        return \Minz\Time::fromNow($next_fetched_at_as_minutes, 'minutes');
    }
}
