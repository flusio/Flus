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
class Opml extends BaseController
{
    /**
     * Show the sources of a stream as an OPML file.
     *
     * @request_param string id
     *
     * @response 200
     *    On success.
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

        auth\Access::require($user, 'view', $stream);

        utils\Locale::setCurrentLocale($stream->owner()->locale);

        $sources = $stream->sources([
            'context_user' => null,
        ]);

        return Response::ok('streams/opml/show.opml.xml.twig', [
            'stream' => $stream,
            'sources' => $sources,
        ]);
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /streams/:id/opml.xml
     */
    public function alias(Request $request): Response
    {
        $stream_id = $request->parameters->getString('id');
        $url = \Minz\Url::for('stream opml', ['id' => $stream_id]);

        return Response::movedPermanently($url);
    }
}
