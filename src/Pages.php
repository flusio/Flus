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
     * @response 302 /news if connected
     * @response 302 /login else
     *
     * @return \Minz\Response
     */
    public function home()
    {
        if (utils\CurrentUser::get()) {
            return Response::redirect('news');
        } else {
            return Response::redirect('login');
        }
    }

    /**
     * Show the terms of service.
     *
     * @response 404
     *     if the policies/legals.html file doesn’t exist
     * @response 200
     *     on success
     *
     * @return \Minz\Response
     */
    public function terms()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        $terms = @file_get_contents($terms_path);
        if (!$terms) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('pages/terms.phtml', [
            'terms' => $terms,
        ]);
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
