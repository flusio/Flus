<?php

namespace App\controllers\my;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Info extends BaseController
{
    /**
     * Return some useful information for the browser extension
     *
     * @response 302 /login?redirect_to=/my/info.json
     *    If the user is not connected
     * @response 200
     *    On success
     */
    public function show(Request $request): Response
    {
        $user = $this->requireCurrentUser(redirect_after_login: \Minz\Url::for('profile info'));
        $bookmarks = $user->bookmarks();
        $links = $bookmarks->links();

        return Response::json(200, [
            'csrf' => \App\Csrf::generate(),
            'bookmarks_id' => $bookmarks->id,
            'bookmarked_urls' => array_column($links, 'url'),
        ]);
    }
}
