<?php

namespace App\controllers\api\v1;

use App\auth;
use App\forms\api as forms;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Searches extends BaseController
{
    /**
     * @json_param string url
     *
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 400
     *     If the URL is invalid.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $json_request = $this->toJsonRequest($request);

        $form = new forms\Search($user);
        $form->handleRequest($json_request);

        if (!$form->validate()) {
            return $this->badRequest($form->errors(format: false));
        }

        $link = $form->link();
        $feeds = $form->feeds();

        return Response::json(200, [
            'links' => [
                $link->toJson(context_user: $user)
            ],
            'feeds' => array_map(function (models\Collection $feed) use ($user): array {
                return $feed->toJson(context_user: $user);
            }, $feeds),
        ]);
    }
}
