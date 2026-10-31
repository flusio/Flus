<?php

namespace App\cli;

use Minz\Request;
use Minz\Response;
use App\jobs;
use App\models;
use App\services;
use App\utils;

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
    public function index(Request $request): Response
    {
        $feeds = models\Feed::listAll();
        $feeds_as_text = [];
        foreach ($feeds as $feed) {
            $feeds_as_text[] = "{$feed->id} {$feed->feed_url}";
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
    public function add(Request $request): Response
    {
        $feed_url = $request->parameters->getString('url', '');
        $feed = models\Feed::init($feed_url);

        if (!$feed->validate()) {
            $errors = implode(' ', $feed->errors());
            return Response::text(400, "Feed creation failed: {$errors}");
        }

        $existing_feed = models\Feed::findBy([
            'feed_url' => $feed->feed_url,
        ]);
        if ($existing_feed) {
            return Response::text(400, 'Feed already in database.');
        }

        // create the feed before fetching it
        $feed->save();

        $fetch_service = new services\FeedFetcher();
        $fetch_service->fetch($feed);

        if ($feed->feed_fetched_error) {
            $error = $feed->feed_fetched_error;
            return Response::text(400, "Feed {$feed->feed_url} has been added but cannot be fetched: {$error}.");
        }

        return Response::text(200, "Feed {$feed->feed_url} ({$feed->name}) has been added.");
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
    public function sync(Request $request): Response
    {
        $id = $request->parameters->getString('id');
        $nocache = $request->parameters->getBoolean('nocache');
        $feed = models\Feed::find($id ?? '');
        if (!$feed) {
            return Response::text(404, "Feed id `{$id}` does not exist.");
        }

        $feed_fetcher_service = new services\FeedFetcher([
            'ignore_cache' => $nocache,
        ]);
        $feed_fetcher_service->fetch($feed);

        return Response::text(200, "Feed {$id} ({$feed->feed_url}) has been synchronized.");
    }

    /**
     * Reset all the feeds last_hash.
     *
     * @response 200
     */
    public function resetHashes(Request $request): Response
    {
        models\Feed::resetHashes();
        return Response::text(200, 'Feeds hashes have been reset.');
    }
}
