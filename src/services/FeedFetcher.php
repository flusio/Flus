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
            default_cache_duration: 1 * 60 * 60,
            min_cache_duration: 1 * 60 * 60,
            max_cache_duration: 1 * 60 * 60 * 24,
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

        // As the frequency evolves each day (= it depends on the current date),
        // and the feed can be in error during several days, it's important to
        // sync the frequency at the beginning of the method (and also at then
        // end, once the links have been imported).
        $collection->syncPublicationFrequencyPerYear();

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

        // Extract the URLs and the ids of the entries first, so we only load
        // the existing links of the collection that match these entries.
        $entries = [];
        $urls = [];
        $feed_entry_ids = [];

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

            if ($entry->id) {
                $feed_entry_id = $entry->id;
            } else {
                $feed_entry_id = $url;
            }

            $entries[] = $entry;
            $urls[] = $url;
            $feed_entry_ids[] = $feed_entry_id;
        }

        // Exclude the entries that don't match the minimum/maximum or period
        // constraints. The keys are preserved, so the URLs and the ids can be
        // filtered as well.
        $entries = $this->filterEntries($entries);
        $urls = array_intersect_key($urls, $entries);
        $feed_entry_ids = array_intersect_key($feed_entry_ids, $entries);

        $link_ids_by_urls = models\Link::listUrlsToIdsByCollection($collection, $urls);
        $link_urls_by_entry_ids = models\Link::listEntryIdsToUrlsByCollection($collection, $feed_entry_ids);
        $owner = $collection->owner();

        $links_to_create = [];
        $links_to_collections_to_create = [];

        foreach ($entries as $index => $entry) {
            $url = $urls[$index];
            $feed_entry_id = $feed_entry_ids[$index];

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
                $link = new models\Link($url, $owner, false);
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

        // We now can insert the links and links_to_collections via a bulkInsert
        models\Link::bulkInsert($links_to_create);
        models\LinkToCollection::bulkInsert($links_to_collections_to_create);

        $collection->syncPublicationFrequencyPerYear();

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
     * Filter the entries of a feed in order to keep only the ones that match
     * the minimum/maximum and period constraints as they would be deleted by
     * the Cleaner otherwise.
     *
     * The keys of the array are preserved.
     *
     * @see \App\models\links\Prunable::deleteFromFeeds()
     *
     * @param array<int, \SpiderBits\feeds\Entry> $entries
     *
     * @return array<int, \SpiderBits\feeds\Entry>
     */
    private function filterEntries(array $entries): array
    {
        $feeds_links_keep_minimum = \App\Configuration::$application['feeds_links_keep_minimum'];
        $feeds_links_keep_maximum = \App\Configuration::$application['feeds_links_keep_maximum'];
        $feeds_links_keep_period = \App\Configuration::$application['feeds_links_keep_period'];

        if ($feeds_links_keep_period === 0 && $feeds_links_keep_maximum === 0) {
            // If no retention policy, we return all the entries
            return $entries;
        }

        $feeds_keep_links_date = \Minz\Time::ago($feeds_links_keep_period, 'months');
        $now = \Minz\Time::now();

        // Sort the entries by their publication dates (newest first). The
        // sort is stable, so the entries without publication date keep the
        // order of the feed.
        uasort($entries, function ($entry1, $entry2) use ($now): int {
            return ($entry2->published_at ?? $now) <=> ($entry1->published_at ?? $now);
        });

        $kept_entries = [];
        foreach ($entries as $index => $entry) {
            $published_at = $entry->published_at ?? $now;
            $recent_enough = (
                $feeds_links_keep_period === 0 ||
                $published_at >= $feeds_keep_links_date
            );
            $enough_entries = count($kept_entries) >= $feeds_links_keep_minimum;
            $too_many_entries = (
                $feeds_links_keep_maximum > 0 &&
                count($kept_entries) >= $feeds_links_keep_maximum
            );
            $should_be_kept = ($recent_enough || !$enough_entries) && !$too_many_entries;

            if ($should_be_kept) {
                $kept_entries[$index] = $entry;
            } else {
                // Because the entries are sorted by publication date, we know
                // that either the next will also be too old, or we already
                // reached the limit of entries. So we can stop here.
                break;
            }
        }

        return $kept_entries;
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

        $publication_frequency = $feed->publication_frequency_per_year;

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
