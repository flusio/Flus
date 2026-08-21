<?php

namespace App\controllers\profiles;

use App\controllers\BaseController;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Streams extends BaseController
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

        $streams = $user->streams([
            'is_private' => false,
            'with_has_unread_links' => false,
        ]);

        return Response::ok('profiles/streams/index.html.twig', [
            'user' => $user,
            'streams' => $streams,
        ]);
    }
}
