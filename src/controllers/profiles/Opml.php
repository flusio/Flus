<?php

namespace App\controllers\profiles;

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
     * Show the collections of a user as an OPML file.
     *
     * @request_param string id
     *
     * @response 404
     *    If the requested profile is associated to the support user.
     * @response 200
     *    On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the user doesn't exist.
     */
    public function show(Request $request): Response
    {
        $user = models\User::requireFromRequest($request);

        if ($user->isSupportUser()) {
            return Response::notFound('errors/not_found.html.twig');
        }

        utils\Locale::setCurrentLocale($user->locale);
        $collections = $user->collections([], [
            'private' => false,
        ]);
        $collections = utils\Sorter::localeSort($collections, 'name');

        return Response::ok('profiles/opml/show.opml.xml.twig', [
            'user' => $user,
            'collections' => $collections,
        ]);
    }

    /**
     * Alias for the show method.
     *
     * @request_param string id
     *
     * @response 301 /p/:id/opml.xml
     */
    public function alias(Request $request): Response
    {
        $user_id = $request->parameters->getString('id');
        $url = \Minz\Url::for('profile opml', ['id' => $user_id]);

        return Response::movedPermanently($url);
    }
}
