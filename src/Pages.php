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
     * @return \Minz\Response
     */
    public function home()
    {
        return Response::ok('pages/home.phtml');
    }

    /**
     * Show the about page.
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
     * @return \Minz\Response
     */
    public function design()
    {
        return Response::ok('pages/design.phtml');
    }
}
