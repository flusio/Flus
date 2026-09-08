<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\services;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds extends BaseController
{
    /**
     * Display a notice pointing to the Sources page.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function index(Request $request): Response
    {
        auth\CurrentUser::require();

        return Response::ok('feeds/index.html.twig');
    }

    /**
     * Show the page to add a feed.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\collections\NewFeed();

        return Response::ok('feeds/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Create a feed if needed, and add the current user as a follower.
     *
     * @request_param string url
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /collections/:id
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\collections\NewFeed();

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('feeds/new.html.twig', [
                'form' => $form,
            ]);
        }

        $feed = $form->feed();

        if (!$feed->isPersisted()) {
            $feed_fetcher_service = new services\FeedFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);
            $feed_fetcher_service->fetch($feed);
        }

        $is_following = $user->isFollowing($feed);
        if (!$is_following) {
            $user->follow($feed);
        }

        return Response::redirect('collection', ['id' => $feed->id]);
    }

    /**
     * Redirect to the "what is new" feed.
     *
     * @response 302 /collections/:id
     */
    public function whatIsNew(): Response
    {
        $feed_url = \App\Configuration::$application['feed_what_is_new'];

        $feed = models\Collection::findBy([
            'type' => 'feed',
            'feed_url' => $feed_url,
        ]);
        if (!$feed) {
            $feed_fetcher_service = new services\FeedFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);

            $feed = models\Collection::initFeed($feed_url);
            $feed_fetcher_service->fetch($feed);
        }

        return Response::redirect('collection', ['id' => $feed->id]);
    }

    /**
     * Return a XSL file to style the feeds.
     *
     * @response 200
     */
    public function xsl(Request $request): Response
    {
        return Response::ok('feeds/feeds.xsl.twig');
    }
}
