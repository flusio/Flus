<?php

namespace App\controllers;

use App\auth;
use Minz\Request;
use Minz\Response;

/**
 * Handle the requests to the static pages of the application.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Pages extends BaseController
{
    /**
     * Show the home page.
     *
     * @response 302 /login
     *     If the user is not connected.
     * @response 302 /news
     *     If the user is connected.
     */
    public function home(): Response
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
     *     If the policies/legals.html file doesn’t exist.
     * @response 200
     *     On success.
     */
    public function terms(): Response
    {
        $app_path = \App\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        $terms = @file_get_contents($terms_path);
        if (!$terms) {
            return Response::notFound('errors/not_found.html.twig');
        }

        return Response::ok('pages/terms.html.twig', [
            'terms' => $terms,
        ]);
    }

    /**
     * Show the addons page.
     *
     * @response 200
     *     On success.
     */
    public function addons(Request $request): Response
    {
        return Response::ok('pages/addons.html.twig');
    }

    /**
     * Show the about page.
     *
     * @response 200
     *     On success.
     */
    public function about(): Response
    {
        return Response::ok('pages/about.html.twig');
    }

    /**
     * Show the robots page.
     *
     * @response 200
     *     On success.
     */
    public function robots(): Response
    {
        return Response::ok('pages/robots.txt', [
            'opened' => \App\Configuration::$application['registrations_opened'],
        ]);
    }

    /**
     * Show the webmanifest page.
     *
     * @response 200
     *     On success.
     */
    public function webmanifest(): Response
    {
        $response = Response::ok('pages/webmanifest.json.php');
        $response->setHeader('Content-Type', 'application/manifest+json');
        return $response;
    }
}
