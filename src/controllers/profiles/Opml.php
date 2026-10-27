<?php

namespace App\controllers\profiles;

use App\auth;
use App\controllers\BaseController;
use App\models;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml extends BaseController
{
    use \App\controllers\traits\OpmlResponder;

    /**
     * Show the collections of a user as an OPML file.
     *
     * @request_param string id
     *
     * @response 200
     *    On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the user doesn't exist.
     */
    public function show(Request $request): Response
    {
        $current_user = auth\CurrentUser::get();
        $user = models\User::requireFromRequest($request);

        utils\Locale::setCurrentLocale($user->locale);

        $opml_generator = new services\OpmlGenerator();
        $opml = $opml_generator->renderCollections($user, [
            'context_user' => $current_user,
        ]);

        return $this->opmlResponse($opml, $user->username);
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
