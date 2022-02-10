<?php

namespace flusio\controllers\collections;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

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
     *
     * @response 404
     *     if the collection doesn’t exist or is inaccessible
     * @response 301 :feed_url
     *     if the collection is a feed
     * @response 200
     */
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        $collection_id = $request->param('id');
        $collection = models\Collection::find($collection_id);

        if (!auth\CollectionsAccess::canView($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        if ($collection->type === 'feed') {
            return Response::movedPermanently($collection->feed_url);
        }

        $locale = $collection->owner()->locale;
        utils\Locale::setCurrentLocale($locale);

        $topics = $collection->topics();
        utils\Sorter::localeSort($topics, 'label');

        $links = $collection->links(['published_at'], [
            'hidden' => false,
            'limit' => 30,
        ]);

        $response = Response::ok('collections/feeds/show.atom.xml.php', [
            'collection' => $collection,
            'topics' => $topics,
            'links' => $links,
            'user_agent' => \Minz\Configuration::$application['user_agent'],
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
    public function alias($request)
    {
        $collection_id = $request->param('id');
        $url = \Minz\Url::for('collection feed', ['id' => $collection_id]);
        return Response::movedPermanently($url);
    }
}
