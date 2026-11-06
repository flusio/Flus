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
     * @response 200
     *    On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the user doesn't exist.
     */
    public function index(Request $request): Response
    {
        $user = models\User::requireFromRequest($request);

        $current_user = auth\CurrentUser::get();

        $collections = $user->collections([
            'private' => false,
            'with_visible_links' => true,
        ]);
        $collections = utils\Sorter::localeSort($collections, 'name');

        models\link_lists\Preloader::for($collections)
            ->countLinksFor($current_user);

        return Response::ok('profiles/collections/index.html.twig', [
            'user' => $user,
            'collections' => $collections,
        ]);
    }
}
