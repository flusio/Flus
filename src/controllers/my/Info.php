<?php

namespace flusio\controllers\my;

use Minz\Request;
use Minz\Response;
use flusio\auth;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Info
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
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('profile info'),
            ]);
        }

        $bookmarks = $user->bookmarks();
        $links = $bookmarks->links();

        return Response::json(200, [
            'csrf' => $user->csrf,
            'bookmarks_id' => $bookmarks->id,
            'bookmarked_urls' => array_column($links, 'url'),
        ]);
    }
}
