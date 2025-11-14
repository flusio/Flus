<?php

namespace App\controllers\api\v1\journal;

use App\auth;
use App\controllers\api\v1\BaseController;
use App\forms\api as forms;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Read extends BaseController
{
    /**
     * @json_param date date
     * @json_param string source
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $json_request = $this->toJsonRequest($request);

        $form = new forms\EmptyJournal($user);
        $form->handleRequest($json_request);

        $links = $form->links();
        $user->markAsRead($links);

        return Response::json(200, []);
    }
}
