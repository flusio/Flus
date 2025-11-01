<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds extends BaseController
{
    /**
     * Show the feed of a collection.
     *
     * @request_param string id
     * @request_param boolean direct
     *     Indicate if <link rel=alternate> should point directly to the
     *     external websites (true) or not (false, default).
     *
     * @response 301 :feed_url
     *     If the collection is a feed.
     * @response 200
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $collection = models\Collection::requireFromRequest($request);

        $direct = $request->parameters->getBoolean('direct');

        auth\Access::require($user, 'view', $collection);

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
            'user_agent' => utils\UserAgent::get(),
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
        $collection_id = $request->parameters->getString('id');
        $url = \Minz\Url::for('collection feed', ['id' => $collection_id]);

        $query_string = $_SERVER['QUERY_STRING'] ?? null;
        if (is_string($query_string) && $query_string) {
            $url .= '?' . $query_string;
        }

        return Response::movedPermanently($url);
    }
}
