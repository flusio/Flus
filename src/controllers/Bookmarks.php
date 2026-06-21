<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\services;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Bookmarks extends BaseController
{
    /**
     * Show the page to mark a url to read later.
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $form = new forms\links\NewLinkSimple();

        return Response::ok('bookmarks/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Mark a url to read later.
     *
     * @request_param string url
     * @request_param boolean is_hidden
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $url = $request->parameters->getString('url', '');

        $link = $user->findOrBuildLink($url);
        $form = new forms\links\NewLinkSimple(model: $link);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('bookmarks/new.html.twig', [
                'form' => $form,
            ]);
        }

        $link = $form->model();

        if (!$link->isPersisted()) {
            $link_fetcher_service = new services\LinkFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);
            $link_fetcher_service->fetch($link);
        }

        $user->markAsReadLater($link);

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
     * Show the bookmarks page.
     *
     * @request_param integer page
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws utils\PaginationOutOfBoundsError
     *     If the requested page is out of the pagination bounds.
     */
    public function index(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $bookmarks = $user->bookmarks();
        $page = $request->parameters->getInteger('page', 1);

        $number_links = models\Link::countByCollectionId($bookmarks->id);
        $pagination = new utils\Pagination($number_links, 29, $page);

        $links = $bookmarks->links(
            ['published_at', 'number_notes'],
            [
                'offset' => $pagination->currentOffset(),
                'limit' => $pagination->numberPerPage(),
            ]
        );

        return Response::ok('bookmarks/index.html.twig', [
            'collection' => $bookmarks,
            'links' => $links,
            'pagination' => $pagination,
        ]);
    }
}
