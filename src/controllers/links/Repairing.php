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
class Repairing
{
    /**
     * Show the page to repair a link (change URL and resynchronize it).
     *
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected.
     * @response 404
     *     If the link doesn't exist or not accessible by the current user.
     * @response 200
     *     On success.
     */
    public function new($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $from = $request->param('from');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        $can_update = auth\LinksAccess::canUpdate($user, $link);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('links/repairing/new.phtml', [
            'link' => $link,
            'url' => $link->url,
            'url_cleared' => \SpiderBits\ClearUrls::clear($link->url),
            'ask_sync' => false,
            'from' => $from,
        ]);
    }

    /**
     * Repair a link (change URL and resynchronize it).
     *
     * @request_param string id
     * @request_param string url
     * @request_param boolean ask_sync
     * @request_param string from
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from
     *     If the user is not connected.
     * @response 404
     *     If the link doesn't exist or not accessible by the current user.
     * @response 400
     *     If the csrf or url is invalid.
     * @response 302 :from
     *     On success.
     */
    public function create($request)
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id');
        $url = $request->param('url', '');
        $ask_sync = $request->paramBoolean('ask_sync', false);
        $csrf = $request->param('csrf');
        $from = $request->param('from');
        $url_cleared = \SpiderBits\ClearUrls::clear($url);

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        $can_update = auth\LinksAccess::canUpdate($user, $link);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        if ($request->isAccepting('text/vnd.turbo-stream.html')) {
            // This allows to display the errors within the modal instead of
            // sending a whole new page. This is a bit hacky so I'm going
            // to use this method only where absolutely needed.
            // @see https://github.com/hotwired/turbo/issues/138#issuecomment-847699281
            $view_file = 'links/repairing/new.turbo_stream.phtml';
        } else {
            $view_file = 'links/repairing/new.phtml';
        }

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest($view_file, [
                'link' => $link,
                'url' => $url,
                'url_cleared' => $url_cleared,
                'ask_sync' => $ask_sync,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $link->url = \SpiderBits\Url::sanitize($url);
        $errors = $link->validate();
        if ($errors) {
            return Response::badRequest($view_file, [
                'link' => $link,
                'url' => $url,
                'url_cleared' => $url_cleared,
                'ask_sync' => $ask_sync,
                'from' => $from,
                'errors' => $errors,
            ]);
        }


        if ($ask_sync) {
            $link_fetcher_service = new services\LinkFetcher([
                'timeout' => 10,
                'rate_limit' => false,
            ]);
            $link_fetcher_service->fetch($link);
        } else {
            $link->save();
        }

        return Response::found($from);
    }
}
