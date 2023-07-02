<?php

namespace flusio\cli;

use Minz\Response;
use flusio\jobs;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds
{
    /**
     * List the feeds.
     *
     * @response 200
     */
    public function index($request)
    {
        $collections = models\Collection::listBy([
            'type' => 'feed',
        ]);
        $feeds_as_text = [];
        foreach ($collections as $collection) {
            $feeds_as_text[] = "{$collection->id} {$collection->feed_url}";
        }

        if (!$feeds_as_text) {
            $feeds_as_text[] = 'No feeds to list.';
        }

        return Response::text(200, implode("\n", $feeds_as_text));
    }

    /**
     * Add a feed to the system.
     *
     * @request_param string url
     *
     * @response 400
     *     if url param is missing, invalid, or if already added
     * @response 200
     */
    public function add($request)
    {
        $feed_url = $request->param('url');
        $user = models\User::supportUser();
        $collection = models\Collection::initFeed($user->id, $feed_url);

        $errors = $collection->validate();
        if ($errors) {
            $errors = implode(' ', $errors);
            return Response::text(400, "Feed collection creation failed: {$errors}");
        }

        $existing_feed = models\Collection::findBy([
            'type' => 'feed',
            'feed_url' => $collection->feed_url,
            'user_id' => $user->id,
        ]);
        if ($existing_feed) {
            return Response::text(400, 'Feed collection already in database.');
        }

        // create the collection before fetching it
        $collection->save();

        $fetch_service = new services\FeedFetcher();
        $fetch_service->fetch($collection);

        if ($collection->feed_fetched_error) {
            $error = $collection->feed_fetched_error;
            return Response::text(400, "Feed {$collection->feed_url} has been added but cannot be fetched: {$error}.");
        }

        return Response::text(200, "Feed {$collection->feed_url} ({$collection->name}) has been added.");
    }

    /**
     * Synchronize a feed.
     *
     * @request_param string id
     * @request_param boolean nocache
     *
     * @response 404
     *     if the id doesn't exist
     * @response 200
     */
    public function sync($request)
    {
        $id = $request->param('id');
        $nocache = $request->paramBoolean('nocache', false);
        $collection = models\Collection::findBy([
            'type' => 'feed',
            'id' => $id,
        ]);
        if (!$collection) {
            return Response::text(404, "Feed id `{$id}` does not exist.");
        }

        $feed_fetcher_service = new services\FeedFetcher([
            'cache' => !$nocache,
        ]);
        $feed_fetcher_service->fetch($collection);

        return Response::text(200, "Feed {$id} ({$collection->feed_url}) has been synchronized.");
    }

    /**
     * Reset all the feeds last_hash.
     *
     * @response 200
     */
    public function resetHashes($request)
    {
        models\Collection::resetHashes();
        return Response::text(200, 'Feeds hashes have been reset.');
    }
}
