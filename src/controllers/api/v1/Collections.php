<?php

namespace App\controllers\api\v1;

use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collections extends BaseController
{
    /**
     * @response 401
     *     If the request is not correctly authenticated.
     * @response 200
     */
    public function index(): Response
    {
        $user = $this->requireCurrentUser();

        $collections = [];
        foreach ($user->collections() as $collection) {
            $collections[] = [
                'id' => $collection->id,
                'name' => $collection->name,
                'description' => $collection->description,
                'group' => $collection->groupForUser($user->id)?->name,
                'is_public' => $collection->is_public,
            ];
        }

        return Response::json(200, $collections);
    }
}
