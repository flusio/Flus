<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds extends BaseController
{
    /**
     * List the followed feeds/collections of the current user.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $groups = models\Group::listBy(['user_id' => $user->id]);
        $groups = utils\Sorter::localeSort($groups, 'name');

        // Counting links is optimized for feeds, so we list the collections in
        // two steps.
        $feeds = $user->followedCollections(['number_links'], [
            'type' => 'feed',
        ]);
        $collections = $user->followedCollections(['number_links'], [
            'type' => 'collection',
        ]);
        $collections = array_merge($collections, $feeds);
        $collections = utils\Sorter::localeSort($collections, 'name');
        $groups_to_collections = utils\Grouper::groupBy($collections, 'group_id');

        return Response::ok('feeds/index.phtml', [
            'groups' => $groups,
            'groups_to_collections' => $groups_to_collections,
        ]);
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

        return Response::ok('feeds/new.phtml', [
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
            return Response::badRequest('feeds/new.phtml', [
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

        $is_following = $user->isFollowing($feed->id);
        if (!$is_following) {
            $user->follow($feed->id);
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
        $support_user = models\User::supportUser();
        $feed_url = \App\Configuration::$application['feed_what_is_new'];

        $feed = models\Collection::findBy([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'user_id' => $support_user->id,
        ]);
        if (!$feed) {
            $feed_fetcher_service = new services\FeedFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);

            $feed = models\Collection::initFeed($support_user->id, $feed_url);
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
        return Response::ok('feeds/feeds.xsl.php');
    }
}
