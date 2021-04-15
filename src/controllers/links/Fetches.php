<?php

namespace flusio\controllers\links;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Fetches
{
    /**
     * Show the fetch link page.
     *
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id/fetch
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 200
     */
    public function show($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show fetch link', ['id' => $link_id]),
            ]);
        }

        $link = models\Link::find($link_id);
        if (auth\LinksAccess::canUpdate($user, $link)) {
            return Response::ok('links/fetches/show.phtml', [
                'link' => $link,
            ]);
        } else {
            return Response::notFound('not_found.phtml');
        }
    }

    /**
     * Fetch information about a link.
     *
     * @request_param string csrf
     * @request_param string id
     *
     * @response 302 /login?redirect_to=/links/:id/fetch
     * @response 404 if the link doesn't exist or not associated to the current user
     * @response 400 if csrf token is invalid
     * @response 302 /links/:id
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');

        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => \Minz\Url::for('show fetch link', ['id' => $link_id]),
            ]);
        }

        $link = models\Link::find($link_id);
        if (!auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $csrf = new \Minz\CSRF();
        if (!$csrf->validateToken($request->param('csrf'))) {
            return Response::badRequest('links/fetches/show.phtml', [
                'link' => $link,
                'error' => _('A security verification failed.'),
            ]);
        }

        $fetch_service = new services\LinkFetcher();
        $fetch_service->fetch($link);

        return Response::redirect('link', ['id' => $link->id]);
    }
}
