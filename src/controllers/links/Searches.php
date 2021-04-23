<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\services;

/**
 * Handle requests to search a link.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Searches
{
    /**
     * Show the page to search by URL.
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/search
     * @response 200
     */
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show search link'),
            ]);
        }

        $support_user = models\User::supportUser();

        $url = $request->param('url', '');
        $url = \SpiderBits\Url::sanitize($url);

        $existing_link = models\Link::daoToModel('findByWithNumberComments', [
            'url' => $url,
            'user_id' => $user->id,
        ]);
        $default_link = models\Link::findBy([
            'url' => $url,
            'user_id' => $support_user->id,
            'is_hidden' => 0,
        ]);

        $feeds = [];
        if (models\FeatureFlag::isEnabled('feeds', $user->id) && $default_link) {
            $feeds = models\Collection::daoToList(
                'listNonEmptyFeedsWithNumberLinks',
                $support_user->id,
                $default_link->feedUrls()
            );
        }

        return Response::ok('links/searches/show.phtml', [
            'url' => $url,
            'default_link' => $default_link,
            'existing_link' => $existing_link,
            'feeds' => $feeds,
        ]);
    }

    /**
     * Search/create a link by URL, and fetch its information.
     *
     * @request_param string csrf
     * @request_param string url
     *
     * @response 302 /login?redirect_to=/links/search
     * @response 400 if csrf token or the URL is invalid
     * @response 302 /links/search?url=:url
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $url = $request->param('url', '');
        $url = \SpiderBits\Url::sanitize($url);
        $support_user = models\User::supportUser();

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show search link'),
            ]);
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('links/searches/show.phtml', [
                'url' => $url,
                'default_link' => null,
                'existing_link' => null,
                'feeds' => [],
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
            return Response::badRequest('links/searches/show.phtml', [
                'url' => $url,
                'default_link' => null,
                'existing_link' => null,
                'feeds' => [],
                'errors' => $errors,
            ]);
        }

        $link_fetcher_service = new services\LinkFetcher();
        $link_fetcher_service->fetch($default_link);

        $feed_fetcher_service = new services\FeedFetcher(false, 10);
        foreach ($default_link->feedUrls() as $feed_url) {
            $existing_feed = models\Collection::findBy([
                'type' => 'feed',
                'feed_url' => $feed_url,
                'user_id' => $support_user->id,
            ]);
            if ($existing_feed) {
                continue;
            }

            $collection = models\Collection::initFeed($support_user->id, $feed_url);
            $feed_fetcher_service->fetch($collection);
        }

        return Response::redirect('show search link', ['url' => $url]);
    }
}
