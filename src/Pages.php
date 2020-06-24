<?php

namespace flusio;

use Minz\Response;

/**
 * Handle the requests to the static pages of the application.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Pages
{
    /**
     * Show the home page.
     *
     * @response 302 /bookmarks if connected
     * @response 200
     *
     * @return \Minz\Response
     */
    public function home()
    {
        if (utils\CurrentUser::get()) {
            return Response::redirect('bookmarks');
        } else {
            return Response::redirect('login');
        }
    }

    /**
     * Show the about page.
     *
     * @response 200
     *
     * @return \Minz\Response
     */
    public function about()
    {
        return Response::ok('pages/about.phtml');
    }

    /**
     * Show the design page.
     *
     * @response 200
     *
     * @return \Minz\Response
     */
    public function design()
    {
        return Response::ok('pages/design.phtml');
    }
}
