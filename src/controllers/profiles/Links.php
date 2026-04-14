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
class Links extends BaseController
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

        $number_links = $user->countLinks([
            'unshared' => false,
        ]);
        $pagination_page = $request->parameters->getInteger('page', 1);
        $pagination = new utils\Pagination($number_links, 30, $pagination_page);

        $links = $user->links(['published_at', 'number_notes'], [
            'unshared' => false,
            'offset' => $pagination->currentOffset(),
            'limit' => $pagination->numberPerPage(),
        ]);

        return Response::ok('profiles/links/index.html.twig', [
            'user' => $user,
            'links' => $links,
            'pagination' => $pagination,
        ]);
    }
}
