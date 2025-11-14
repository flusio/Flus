<?php

namespace App\controllers\api\v1;

use App\auth;
use App\forms\api as forms;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions extends BaseController
{
    /**
     * @json_param string email
     * @json_param string password
     * @json_param string app_name
     *
     * @response 400
     *     If the credentials or app_name are invalid.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $json_request = $this->toJsonRequest($request);

        $form = new forms\Session();
        $form->handleRequest($json_request);

        if (!$form->validate()) {
            return $this->badRequest($form->errors(format: false));
        }

        $session = $form->session();

        return Response::json(200, [
            'token' => $session->token,
        ]);
    }

    /**
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 200
     */
    public function delete(Request $request): Response
    {
        auth\CurrentUser::require();

        auth\CurrentUser::deleteSession();

        return Response::json(200, []);
    }
}
