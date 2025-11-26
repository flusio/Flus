<?php

namespace App\controllers\collections;

use App\auth;
use App\controllers\BaseController;
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
class Links extends BaseController
{
    /**
     * Show the page to add a link to a collection.
     *
     * @request_param string id
     *     The collection id to which to add the new link
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot add links to the collection.
     */
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'addLinks', $collection);

        $form = new forms\collections\AddLinkToCollection();

        return Response::ok('collections/links/new.html.twig', [
            'collection' => $collection,
            'form' => $form,
        ]);
    }

    /**
     * Add a link to the given collection.
     *
     * @request_param string id
     *     The collection id to which to add the new link
     * @request_param string url
     *     The URL of the link to add
     * @request_param boolean is_hidden
     *     Whether the link should be hidden or not
     * @request_param string csrf_token
     *     The CSRF token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot add links to the collection.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'addLinks', $collection);

        $url = $request->parameters->getString('url', '');

        $link = $user->findOrBuildLink($url);
        $form = new forms\collections\AddLinkToCollection(model: $link);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('collections/links/new.html.twig', [
                'collection' => $collection,
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
            $link->save();
        }

        $link->addCollection($collection);

        return Response::found(utils\RequestHelper::from($request));
    }
}
