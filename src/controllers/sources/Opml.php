<?php

namespace App\controllers\sources;

use App\auth;
use App\controllers\BaseController;
use App\services;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Opml extends BaseController
{
    use \App\controllers\traits\OpmlResponder;

    /**
     * Show the sources followed by the current user as an OPML file.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function show(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $opml_generator = new services\OpmlGenerator();
        $opml = $opml_generator->renderFollows($user, [
            'context_user' => $user,
            'with_time_filter' => true,
        ]);

        return $this->opmlResponse($opml, 'sources');
    }
}
