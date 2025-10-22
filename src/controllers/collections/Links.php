<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;
use App\services;
use App\utils;

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
        $collection_id = $request->parameters->getString('id', '');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

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
        $collection_id = $request->parameters->getString('id', '');
        $url = $request->parameters->getString('url', '');
        $is_hidden = $request->parameters->getBoolean('is_hidden');
        $from = $request->parameters->getString('from', '');
        $csrf = $request->parameters->getString('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $collection = models\Collection::find($collection_id);
        if (!$collection || !auth\CollectionsAccess::canAddLinks($user, $collection)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
            return Response::badRequest('collections/links/new.phtml', [
                'collection' => $collection,
                'url' => $url,
                'is_hidden' => $is_hidden,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link = new models\Link($url, $user->id, $is_hidden);

        if (!$link->validate()) {
            return Response::badRequest('collections/links/new.phtml', [
                'collection' => $collection,
                'url' => $url,
                'is_hidden' => $is_hidden,
                'from' => $from,
                'errors' => $link->errors(),
            ]);
        }

        $existing_link = models\Link::findBy([
            'user_id' => $user->id,
            // Can't use $link->url_hash directly since it's a calculated
            // property, generated in database (and the link is not yet saved).
            'url_hash' => models\Link::hashUrl($link->url),
        ]);

        if ($existing_link) {
            $link = $existing_link;
        } else {
            $link_fetcher_service = new services\LinkFetcher([
                'http_timeout' => 10,
                'ignore_rate_limit' => true,
            ]);
            $link_fetcher_service->fetch($link);
        }

        $link->addCollection($collection);

        return Response::found($from);
    }
}
