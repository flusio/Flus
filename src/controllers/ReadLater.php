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
class ReadLater extends BaseController
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

        return Response::ok('read_later/new.html.twig', [
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
            return Response::badRequest('read_later/new.html.twig', [
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
     * Show the read later links.
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
        $read_later_source = $user->readLaterSource();

        $page = $request->parameters->getInteger('page', 1);

        $number_links = $read_later_source->countLinks();
        $pagination = new utils\Pagination($number_links, 29, $page);

        $links = $read_later_source->links($pagination);

        return Response::ok('read_later/index.html.twig', [
            'read_later_source' => $read_later_source,
            'links' => $links,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Handle old /bookmarks URL and redirect to /read/later
     *
     * @response 301 /read/later
     */
    public function bookmarks(Request $request): Response
    {
        $url = \Minz\Url::for('read later');
        return Response::movedPermanently($url);
    }
}
