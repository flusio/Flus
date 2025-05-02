<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Journal extends BaseController
{
    /**
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 200
     */
    public function show(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $news = $user->news();
        $links = $news->links(['published_at', 'number_notes']);

        return Response::json(200, array_map(function (models\Link $link) use ($user): array {
            return $link->toJson(context_user: $user);
        }, $links));
    }

    /**
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 200
     */
    public function create(Request $request): Response
    {
        $user = $this->requireCurrentUser();

        $journal = new models\Journal($user);
        $count = $journal->fill(max: 50);

        return Response::json(200, [
            'count' => $count,
        ]);
    }
}
