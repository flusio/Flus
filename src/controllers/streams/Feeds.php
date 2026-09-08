<?php

namespace App\controllers\streams;

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
     * Show the feed of a stream.
     *
     * @request_param string id
     * @request_param boolean direct
     *     Indicate if <link rel=alternate> should point directly to the
     *     external websites (true) or not (false, default).
     *
     * @response 200
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the stream.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $stream = models\Stream::requireFromRequest($request);

        $direct = $request->parameters->getBoolean('direct');

        auth\Access::require($user, 'view', $stream);

        utils\Locale::setCurrentLocale($stream->owner()->locale);

        $links = $stream->links([
            'context_user' => null,
            'days' => 'ALL',
            'limit' => 30,
        ]);

        // Deduplicate the links by id: a stream can list the same link several
        // times (e.g. two collections publishing it), while the Atom entries
        // must have unique ids. The same URL published by different people
        // (with different notes) is kept though.
        $links_by_id = [];
        foreach ($links as $link) {
            $links_by_id[$link->id] ??= $link;
        }
        $links = array_values($links_by_id);

        models\links\Preloader::for($links)->notes();

        return Response::ok('streams/feeds/show.atom.xml.twig', [
            'stream' => $stream,
            'links' => $links,
            'direct' => $direct,
        ]);
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /streams/:id/feed.atom.xml
     */
    public function alias(Request $request): Response
    {
        $stream_id = $request->parameters->getString('id');
        $url = \Minz\Url::for('stream feed', ['id' => $stream_id]);

        $query_string = $request->server->getString('QUERY_STRING');
        if ($query_string) {
            $url .= '?' . $query_string;
        }

        return Response::movedPermanently($url);
    }
}
