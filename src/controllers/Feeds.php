<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\services;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds extends BaseController
{
    /**
     * List the followed feeds/collections of the current user.
     *
     * @response 302 /login?redirect_to=/feeds
     *     if the user is not connected
     * @response 200
     */
    public function index(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('feeds'));

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
     * @request_param string from
     *     The page to redirect to after creation (default is /feeds)
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 200
     */
    public function new(Request $request): Response
    {
        $from = $request->param('from', \Minz\Url::for('feeds'));

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        return Response::ok('feeds/new.phtml', [
            'url' => '',
            'from' => $from,
        ]);
    }

    /**
     * Create a feed if needed, and add the current user as a follower.
     *
     * @request_param string csrf
     * @request_param string url It must be a valid non-empty URL
     * @request_param string from The page to redirect to if not connected
     *
     * @response 302 /login?redirect_to=:from if not connected
     * @response 400 if CSRF or the url is invalid
     * @response 302 /collections/:id on success
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $url = $request->param('url', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        $url = \SpiderBits\Url::sanitize($url);
        $support_user = models\User::supportUser();

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('feeds/new.phtml', [
                'url' => $url,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $default_link = models\Link::findBy([
            'user_id' => $support_user->id,
            'url_hash' => models\Link::hashUrl($url),
        ]);
        if (!$default_link) {
            $default_link = new models\Link($url, $support_user->id, false);
        }

        $errors = $default_link->validate();
        if ($errors) {
            return Response::badRequest('feeds/new.phtml', [
                'url' => $url,
                'from' => $from,
                'errors' => $errors,
            ]);
        }

        $link_fetcher_service = new services\LinkFetcher([
            'http_timeout' => 10,
            'ignore_rate_limit' => true,
        ]);
        $link_fetcher_service->fetch($default_link);

        if (count($default_link->url_feeds) === 0) {
            return Response::badRequest('feeds/new.phtml', [
                'url' => $url,
                'from' => $from,
                'errors' => [
                    'url' => _('There is no valid feeds at this address.'),
                ],
            ]);
        }

        $feed_url = $default_link->url_feeds[0];
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
