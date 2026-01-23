<?php

namespace App\controllers\links;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\services;
use Minz\Request;
use Minz\Response;

/**
 * Handle requests to search a link.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Searches extends BaseController
{
    /**
     * Show the page to search by URL.
     *
     * @request_param string url
     * @request_param boolean autosubmit
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

        $form = new forms\Search(options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        return Response::ok('links/searches/show.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Search/create a link by URL, and fetch its information.
     *
     * @request_param string url
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 /links/search?url=:url
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\Search(options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/searches/show.html.twig', [
                'form' => $form,
            ]);
        }

        $link_fetcher_service = new services\LinkFetcher();
        $feed_fetcher_service = new services\FeedFetcher();

        $link = $form->link();
        if (!$link->isPersisted()) {
            $link_fetcher_service->fetch($link);
            $link->save();
        }

        $feeds = $form->feeds();
        foreach ($feeds as $feed) {
            if (!$feed->isPersisted()) {
                $feed_fetcher_service->fetch($feed);
                $feed->save();
            }
        }

        return Response::redirect('show search link', ['url' => $form->url]);
    }
}
