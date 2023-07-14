<?php

namespace flusio\controllers\collections;

use Minz\Request;
use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Links
{
    /**
     * Show the page to add a link to a collection.
     *
     * @request_param string id
     *     The collection id to which to add the new link
     * @request_param string from
     *     The page to redirect to after creation
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn't exist, or if user has not write access to it
     * @response 200
     *     On success
     */
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::get();

        $collection_id = $request->param('id', '');
        $from = $request->param('from', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        if (!$collection || !auth\CollectionsAccess::canAddLinks($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('collections/links/new.phtml', [
            'collection' => $collection,
            'url' => '',
            'is_hidden' => false,
            'from' => $from,
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
     * @request_param string from
     *     The page to redirect to after creation
     * @request_param string csrf
     *     The CSRF token
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn't exist, or if user has not write access to it
     * @response 400
     *     If the CSRF or the url is invalid
     * @response 302 :from
     *     On success
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::get();

        $collection_id = $request->param('id', '');
        $url = $request->param('url', '');
        $is_hidden = $request->paramBoolean('is_hidden', false);
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $collection = models\Collection::find($collection_id);
        if (!$collection || !auth\CollectionsAccess::canAddLinks($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('collections/links/new.phtml', [
                'collection' => $collection,
                'url' => $url,
                'is_hidden' => $is_hidden,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link = new models\Link($url, $user->id, $is_hidden);
        $errors = $link->validate();
        if ($errors) {
            return Response::badRequest('collections/links/new.phtml', [
                'collection' => $collection,
                'url' => $url,
                'is_hidden' => $is_hidden,
                'from' => $from,
                'errors' => $errors,
            ]);
        }

        $existing_link = models\Link::findBy([
            'user_id' => $user->id,
            // Can't use $link->url_lookup since it's a calculated property,
            // generated in database (and the link is not yet saved).
            'url_lookup' => utils\Belt::removeScheme($link->url),
        ]);

        if ($existing_link) {
            $link = $existing_link;
        } else {
            $link_fetcher_service = new services\LinkFetcher([
                'timeout' => 10,
                'rate_limit' => false,
            ]);
            $link_fetcher_service->fetch($link);
        }

        models\LinkToCollection::attach([$link->id], [$collection->id]);

        return Response::found($from);
    }
}
