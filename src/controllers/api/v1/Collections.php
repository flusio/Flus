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
     * Show the page listing all the collections of the current user
     *
     * This page is deprecated an no longer used. It has been replaced by the
     * "My links" and "Feeds" pages.
     *
     * @response 301 /links
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
                'is_public' => $collection->is_public,
            ];
        }

        return Response::json(200, $collections);
    }
}
