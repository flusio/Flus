<?php

namespace App\controllers\api\v1;

use App\models;
use App\forms;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sessions extends BaseController
{
    /**
     * @request_param string email
     * @request_param string password
     * @request_param string app_name
     *
     * @response 400
     *     If the credentials or app_name are invalid.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $jsonRequest = $this->toJsonRequest($request);

        $form = new forms\api\NewSession();
        $form->handleRequest($jsonRequest);

        if (!$form->validate()) {
            return $this->badRequestWithForm($form);
        }

        $user = $form->getUser();

        $token = new models\Token(1, 'month');
        $token->save();

        /** @var string */
        $ip = $request->header('REMOTE_ADDR', 'unknown');

        $session = new models\Session($form->app_name, $ip);
        $session->user_id = $user->id;
        $session->token = $token->token;
        $session->save();

        return Response::json(200, [
            'token' => $token->token,
        ]);
    }
}
