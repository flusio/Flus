<?php

namespace App\controllers\sources;

use App\auth;
use App\controllers\BaseController;
use App\models;
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
     * Show the feed of a source.
     *
     * @request_param string id
     * @request_param boolean direct
     *     Indicate if <link rel=alternate> should point directly to the
     *     external websites (true) or not (false, default).
     *
     * @response 301 :feed_url
     *     If the source is a feed.
     * @response 200
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the source doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the source.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $source = models\Source::requireFromRequest($request);

        $direct = $request->parameters->getBoolean('direct');

        auth\Access::require($user, 'view', $source);

        if (!($source instanceof models\Collection)) {
            return Response::movedPermanently($source->feedUrl());
        }

        utils\Locale::setCurrentLocale($source->owner()->locale);

        $topics = $source->topics();
        $topics = utils\Sorter::localeSort($topics, 'label');

        $links = $source->links(['published_at'], [
            'hidden' => false,
            'limit' => 30,
        ]);

        models\links\Preloader::for($links)->notes();

        return Response::ok('sources/feeds/show.atom.xml.twig', [
            'source' => $source,
            'topics' => $topics,
            'links' => $links,
            'direct' => $direct,
        ]);
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
        $source_id = $request->parameters->getString('id');
        $url = \Minz\Url::for('source feed', ['id' => $source_id]);

        $query_string = $request->server->getString('QUERY_STRING');
        if ($query_string) {
            $url .= '?' . $query_string;
        }

        return Response::movedPermanently($url);
    }
}
