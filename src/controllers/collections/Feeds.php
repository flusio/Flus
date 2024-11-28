<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds
{
    /**
     * Show the feed of a collection.
     *
     * @request_param string id
     * @request_param boolean direct
     *     Indicate if <link rel=alternate> should point directly to the
     *     external websites (true) or not (false, default).
     *
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
     * @response 301 :feed_url
     *     if the collection is a feed
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id', '');
        $direct = $request->paramBoolean('direct', false);

        $collection = models\Collection::find($collection_id);
        if (!$collection || !auth\CollectionsAccess::canView($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        if ($collection->type === 'feed') {
            /** @var string */
            $feed_url = $collection->feed_url;
            return Response::movedPermanently($feed_url);
        }

        $locale = $collection->owner()->locale;
        utils\Locale::setCurrentLocale($locale);

        $topics = $collection->topics();
        $topics = utils\Sorter::localeSort($topics, 'label');

        $links = $collection->links(['published_at'], [
            'hidden' => false,
            'limit' => 30,
        ]);

        $response = Response::ok('collections/feeds/show.atom.xml.php', [
            'collection' => $collection,
            'topics' => $topics,
            'links' => $links,
            'user_agent' => \App\Configuration::$application['user_agent'],
            'direct' => $direct,
        ]);
        $response->setHeader('X-Content-Type-Options', 'nosniff');
        return $response;
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /collections/:id/feed.atom.xml
     */
    public function alias(Request $request): Response
    {
        $collection_id = $request->param('id');
        $url = \Minz\Url::for('collection feed', ['id' => $collection_id]);
        if (!empty($_SERVER['QUERY_STRING'])) {
            $url .= '?' . $_SERVER['QUERY_STRING'];
        }
        return Response::movedPermanently($url);
    }
}
