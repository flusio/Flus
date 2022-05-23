<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;

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
        if (auth\CurrentUser::get()) {
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
     * Show the about page.
     *
     * @response 200
     */
    public function about()
    {
        return Response::ok('pages/about.phtml', [
            'version' => \Minz\Configuration::$application['version'],
        ]);
    }

    /**
     * Show the webmanifest page.
     *
     * @response 200
     */
    public function webmanifest()
    {
        $response = Response::ok('pages/webmanifest.json');
        $response->setHeader('Content-Type', 'application/manifest+json');
        return $response;
    }
}
