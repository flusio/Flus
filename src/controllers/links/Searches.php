<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;
use App\services;
use App\utils;

/**
 * Handle requests to search a link.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Searches extends BaseController
{
    /**
     * Show the page to search by URL.
     *
     * @request_param string url
     * @request_param boolean autosubmit
     *
     * @response 302 /login?redirect_to=/links/search
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('show search link'));
        $support_user = models\User::supportUser();

        $autosubmit = $request->parameters->getBoolean('autosubmit');

        $url = $request->parameters->getString('url', '');
        $url = \SpiderBits\Url::sanitize($url);

        $existing_link = models\Link::findComputedBy([
            'user_id' => $user->id,
            'url_hash' => models\Link::hashUrl($url),
        ], ['number_comments']);
        $default_link = models\Link::findBy([
            'user_id' => $support_user->id,
            'url_hash' => models\Link::hashUrl($url),
            'is_hidden' => 0,
        ]);

        $feeds = [];
        if ($default_link) {
            $associated_feeds = models\Collection::listComputedFeedsByFeedUrls(
                $default_link->url_feeds,
                ['number_links']
            );

            // Deduplicate feeds with same names
            foreach ($associated_feeds as $feed) {
                if (!isset($feeds[$feed->name])) {
                    $feeds[$feed->name] = $feed;
                }
            }
        }

        return Response::ok('links/searches/show.phtml', [
            'url' => $url,
            'default_link' => $default_link,
            'existing_link' => $existing_link,
            'feeds' => $feeds,
            'autosubmit' => $autosubmit,
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
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('show search link'));
        $url = $request->parameters->getString('url', '');
        $csrf = $request->parameters->getString('csrf', '');

        $url = \SpiderBits\Url::sanitize($url);
        $support_user = models\User::supportUser();

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('links/searches/show.phtml', [
                'url' => $url,
                'autosubmit' => false,
                'default_link' => null,
                'existing_link' => null,
                'feeds' => [],
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

        if (!$default_link->validate()) {
            return Response::badRequest('links/searches/show.phtml', [
                'url' => $url,
                'autosubmit' => false,
                'default_link' => null,
                'existing_link' => null,
                'feeds' => [],
                'errors' => $default_link->errors(),
            ]);
        }

        $link_fetcher_service = new services\LinkFetcher([
            'http_timeout' => 10,
            'ignore_rate_limit' => true,
        ]);
        $link_fetcher_service->fetch($default_link);

        $feed_fetcher_service = new services\FeedFetcher([
            'http_timeout' => 10,
            'ignore_rate_limit' => true,
        ]);
        foreach ($default_link->url_feeds as $feed_url) {
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
