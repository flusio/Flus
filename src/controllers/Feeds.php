<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
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
     * List the followed feeds/collections of the current user.
     *
     * @response 302 /login?redirect_to=/feeds
     *     if the user is not connected
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => \Minz\Url::for('feeds')]);
        }

        $groups = models\Group::daoToList('listBy', ['user_id' => $user->id]);
        utils\Sorter::localeSort($groups, 'name');

        $collections = $user->followedCollections(['number_links']);
        utils\Sorter::localeSort($collections, 'name');
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
    public function new($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from', \Minz\Url::for('feeds'));

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

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
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $url = $request->param('url', '');
        $from = $request->param('from');
        $csrf = $request->param('csrf');

        $url = \SpiderBits\Url::sanitize($url);
        $support_user = models\User::supportUser();

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        if ($request->isAccepting('text/vnd.turbo-stream.html')) {
            // This allows to display the errors within the modal instead of
            // sending a whole new page. This is a bit hacky so I'm going
            // to use this method only where absolutely needed.
            // @see https://github.com/hotwired/turbo/issues/138#issuecomment-847699281
            $view_file = 'feeds/new.turbo_stream.phtml';
        } else {
            $view_file = 'feeds/new.phtml';
        }

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest($view_file, [
                'url' => $url,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $default_link = models\Link::findBy([
            'user_id' => $support_user->id,
            'url' => $url,
        ]);
        if (!$default_link) {
            $default_link = models\Link::init($url, $support_user->id, false);
        }

        $errors = $default_link->validate();
        if ($errors) {
            return Response::badRequest($view_file, [
                'url' => $url,
                'from' => $from,
                'errors' => $errors,
            ]);
        }

        $link_fetcher_service = new services\LinkFetcher([
            'timeout' => 10,
            'rate_limit' => false,
        ]);
        $link_fetcher_service->fetch($default_link);

        $feed_urls = $default_link->feedUrls();
        if (count($feed_urls) === 0) {
            return Response::badRequest($view_file, [
                'url' => $url,
                'from' => $from,
                'errors' => [
                    'url' => _('There is no valid feeds at this address.'),
                ],
            ]);
        }

        $feed_url = $feed_urls[0];
        $feed = models\Collection::findBy([
            'type' => 'feed',
            'feed_url' => $feed_url,
            'user_id' => $support_user->id,
        ]);
        if (!$feed) {
            $feed_fetcher_service = new services\FeedFetcher([
                'timeout' => 10,
                'rate_limit' => false,
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
}
