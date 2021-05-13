<?php

namespace flusio\controllers\collections;

use Minz\Response;
use flusio\auth;
use flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Bookmarks
{
    /**
     * Show the bookmarks page
     *
     * @response 302 /login?redirect_to=/bookmarks if not connected
     * @response 200
     */
    public function show()
    {
        $user = auth\CurrentUser::get();
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('bookmarks'),
            ]);
        }

        $bookmarks = $user->bookmarks();
        return Response::ok('collections/bookmarks/show.phtml', [
            'collection' => $bookmarks,
            'links' => $bookmarks->links(),
        ]);
    }
}
