<?php

namespace App\services;

use App\models;
use App\utils;

/**
 * Service to import feeds from an OPML file.
 *
 * @phpstan-import-type Outline from \SpiderBits\Opml
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OpmlImportator
{
    private \SpiderBits\Opml $opml;

    /**
     * @throws OpmlImportatorError
     *     If the file cannot be read, or if it cannot be parsed as an OPML
     *     file.
     */
    public function __construct(string $opml_filepath)
    {
        $opml_as_string = @file_get_contents($opml_filepath);

        if ($opml_as_string === false) {
            throw new OpmlImportatorError('Can’t read the OPML file.');
        }

        try {
            $opml = \SpiderBits\Opml::fromText($opml_as_string);
        } catch (\DomainException $e) {
            throw new OpmlImportatorError($e->getMessage());
        }

        $this->opml = $opml;
    }

    /**
     * Perform the importation.
     */
    public function importForUser(models\User $user): void
    {
        $feed_urls_by_streams = $this->loadUrlsFromOutlines($this->opml->outlines, '');

        $collection_ids_by_feed_urls = models\Collection::listFeedUrlsToIds();
        $collections_to_create = [];
        $followed_collections_to_create = [];
        $collection_ids_by_streams = [];

        foreach ($feed_urls_by_streams as $stream_name => $feed_urls) {
            $stream_id = null;
            if ($stream_name) {
                // If there is a stream name, we want to make sure it exists in
                // database, and get its id to attach the sources to it.
                $stream_name = utils\Belt::cut($stream_name, models\Stream::NAME_MAX_LENGTH);

                $stream = models\Stream::findOrCreateBy([
                    'name' => $stream_name,
                    'user_id' => $user->id,
                ], [
                    'id' => \Minz\Random::timebased(),
                ]);

                $stream_id = $stream->id;
            }

            foreach ($feed_urls as $feed_url) {
                $feed_url = \SpiderBits\Url::sanitize($feed_url);

                if (isset($collection_ids_by_feed_urls[$feed_url])) {
                    $collection_id = $collection_ids_by_feed_urls[$feed_url];
                } else {
                    $collection = models\Collection::initFeed($feed_url);
                    $collection->created_at = \Minz\Time::now();

                    $collections_to_create[] = $collection;

                    $collection_ids_by_feed_urls[$feed_url] = $collection->id;
                    $collection_id = $collection->id;
                }

                $followed_collection = new models\FollowedCollection($user->id, $collection_id);
                $followed_collection->created_at = \Minz\Time::now();

                $followed_collections_to_create[] = $followed_collection;

                if ($stream_id) {
                    $collection_ids_by_streams[$stream_id][] = $collection_id;
                }
            }
        }

        models\Collection::bulkInsert($collections_to_create);
        models\FollowedCollection::bulkInsert($followed_collections_to_create);

        // Now, create the associated streams. Streams depend on
        // FollowedCollections, so we get them from the database.
        $follows = models\FollowedCollection::listBy(['user_id' => $user->id]);
        $follows_by_collection_ids = array_column($follows, null, 'collection_id');

        $streams_to_follows_to_create = [];

        foreach ($collection_ids_by_streams as $stream_id => $collection_ids) {
            foreach ($collection_ids as $collection_id) {
                $follow = $follows_by_collection_ids[$collection_id] ?? null;

                if (!$follow) {
                    // This should not happen as we created the follows via
                    // $followed_collections_to_create, which is always
                    // populated when we populate $collection_ids_by_streams.
                    // Anyway, better be sure that it doesn't fail.
                    continue;
                }

                // We always build a StreamToFollow, without verifying that it
                // already exists in the database: if it does, `bulkInsert` will
                // ignore the conflict (uniqueness on stream_id and follow_id).
                $stream_to_follow = new models\StreamToFollow();
                $stream_to_follow->created_at = \Minz\Time::now();
                $stream_to_follow->stream_id = $stream_id;
                $stream_to_follow->follow_id = $follow->id;

                $streams_to_follows_to_create[] = $stream_to_follow;
            }
        }

        models\StreamToFollow::bulkInsert($streams_to_follows_to_create);
    }

    /**
     * Return the list of xmlUrl by stream name of OPML outlines and their children.
     *
     * @param Outline[] $outlines
     * @param string $parent_stream_name
     *
     * @return array<string, string[]>
     */
    private function loadUrlsFromOutlines(array $outlines, string $parent_stream_name): array
    {
        $urls_by_streams = [];

        foreach ($outlines as $outline) {
            // Get the urls from child outline (it may return several urls if
            // the outline is a stream).
            $outline_urls_by_streams = $this->loadUrlsFromOutline($outline, $parent_stream_name);

            // Then, we merge the initial array with the array returned by the
            // outline.
            foreach ($outline_urls_by_streams as $stream_name => $urls) {
                if (!isset($urls_by_streams[$stream_name])) {
                    $urls_by_streams[$stream_name] = [];
                }

                $urls_by_streams[$stream_name] = array_merge(
                    $urls_by_streams[$stream_name],
                    $urls
                );
            }
        }

        return $urls_by_streams;
    }

    /**
     * Return the list of xmlUrl of an OPML outline and its children.
     *
     * @param Outline $outline
     * @param string $parent_stream_name
     *
     * @return array<string, string[]>
     */
    private function loadUrlsFromOutline(array $outline, string $parent_stream_name): array
    {
        $urls_by_streams = [];

        if ($outline['outlines'] && is_array($outline['outlines'])) {
            // The outline has children, it's probably a new stream
            $text = $outline['text'] ?? '';
            if (is_string($text) && !empty($text)) {
                $stream_name = trim($text);
            } else {
                $stream_name = $parent_stream_name;
            }

            /** @var Outline[] */
            $outlines = $outline['outlines'];
            $urls_by_streams = $this->loadUrlsFromOutlines($outlines, $stream_name);
        }

        if (!isset($urls_by_streams[$parent_stream_name])) {
            $urls_by_streams[$parent_stream_name] = [];
        }

        if (is_string($outline['xmlUrl'] ?? null)) {
            // The xmlUrl means it's a feed URL: we add it to the array
            $urls_by_streams[$parent_stream_name][] = $outline['xmlUrl'];
        }

        return $urls_by_streams;
    }
}
