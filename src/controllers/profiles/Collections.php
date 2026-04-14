<?php

namespace App\controllers\profiles;

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
class Collections extends BaseController
{
    /**
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
    public function index(Request $request): Response
    {
        $user = models\User::requireFromRequest($request);

        if ($user->isSupportUser()) {
            return Response::notFound('errors/not_found.html.twig');
        }

        $current_user = auth\CurrentUser::get();

        $collections = $user->collections(['number_links'], [
            'private' => false,
            'count_hidden' => false,
        ]);
        $collections = utils\Sorter::localeSort($collections, 'name');

        return Response::ok('profiles/collections/index.html.twig', [
            'user' => $user,
            'collections' => $collections,
        ]);
    }
}
