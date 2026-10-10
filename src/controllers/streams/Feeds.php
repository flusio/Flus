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
     * @request_param string view
     *     The id of the view to apply, or "default" for the main view. If
     *     not given, no view is applied and the feed lists the links of all
     *     the sources.
     * @request_param boolean direct
     *     Indicate if <link rel=alternate> should point directly to the
     *     external websites (true) or not (false, default).
     *
     * @response 200
     *     On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the stream or the view doesn't exist.
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

        $view = null;
        $links_options = [
            'context_user' => null,
        ];

        if ($request->parameters->has('view')) {
            $view = $this->requireView($stream, $request);

            // Only the saved parameters of the view are applied: the
            // parameters of the URL must not change the content of the feed.
            $view->setStream($stream);
            $view->loadUrlParameters(new \Minz\ParameterBag([]));

            $stream_view = new models\StreamView($stream, null, $view);
            $links_options = $stream_view->linksOptions();
        }

        if ($links_options === null) {
            $links = [];
        } else {
            // The dates of the view are ignored as a feed slides anyway.
            $links = $stream->links(array_merge($links_options, [
                'days' => 'ALL',
                'limit' => 30,
            ]));
        }

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
            'view' => $view,
            'links' => $links,
            'direct' => $direct,
        ]);
    }

    /**
     * Return the view of the stream designated by the "view" parameter.
     *
     * The main view is designated by "default" rather than by its id: it is
     * deleted when it is reset, while its feed must keep working.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the view doesn't exist or doesn't belong to the stream.
     */
    private function requireView(models\Stream $stream, Request $request): models\View
    {
        if ($request->parameters->getString('view') === 'default') {
            return $stream->defaultView();
        }

        $view = models\View::loadFromRequest($request, parameter: 'view');

        if (!$view || $view->stream_id !== $stream->id) {
            throw new \Minz\Errors\MissingRecordError('The view does not exist.');
        }

        return $view;
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
