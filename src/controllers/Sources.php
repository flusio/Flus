<?php

namespace App\controllers;

use App\auth;
use App\models;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Sources extends BaseController
{
    /**
     * List the sources followed by the current user.
     *
     * @response 302 /feeds
     *     If the user has not the alpha feature enabled.
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        if (!$user->isAlphaEnabled()) {
            return Response::redirect('feeds');
        }

        $sources = $user->followedSources();

        models\collections\Preloader::for($sources)
            ->publishers()
            ->countStreamsFor($user)
            ->followsFor($user);

        return Response::ok('sources/index.html.twig', [
            'sources' => $sources,
        ]);
    }
}
