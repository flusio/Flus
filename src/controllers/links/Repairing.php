<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;
use App\services;

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
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id', '');
        $from = $request->param('from', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        $can_update = $link && auth\LinksAccess::canUpdate($user, $link);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('links/repairing/new.phtml', [
            'link' => $link,
            'url' => $link->url,
            'url_cleared' => \SpiderBits\ClearUrls::clear($link->url),
            'force_sync' => $link->title === $link->url,
            'from' => $from,
        ]);
    }

    /**
     * Repair a link (change URL and resynchronize it).
     *
     * @request_param string id
     * @request_param string url
     * @request_param boolean force_sync
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
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $link_id = $request->param('id', '');
        $url = $request->param('url', '');
        $force_sync = $request->paramBoolean('force_sync', false);
        $csrf = $request->param('csrf', '');
        $from = $request->param('from', '');

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => $from]);
        }

        $link = models\Link::find($link_id);
        $can_update = $link && auth\LinksAccess::canUpdate($user, $link);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('links/repairing/new.phtml', [
                'link' => $link,
                'url' => $url,
                'url_cleared' => $url,
                'force_sync' => $force_sync,
                'from' => $from,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $old_link = models\Link::copy($link, $user->id);

        $link->url = \SpiderBits\Url::sanitize($url);
        $errors = $link->validate();
        if ($errors) {
            return Response::badRequest('links/repairing/new.phtml', [
                'link' => $link,
                'url' => $url,
                'url_cleared' => $url,
                'force_sync' => $force_sync,
                'from' => $from,
                'errors' => $errors,
            ]);
        }

        $link_fetcher_service = new services\LinkFetcher([
            'timeout' => 10,
            'rate_limit' => false,
            'force_sync' => $force_sync,
        ]);
        $link_fetcher_service->fetch($link);

        // Add the old link to the never list. It avoids to a link coming from
        // the news to reappear.
        $old_link->save();
        models\LinkToCollection::markToNeverRead($user, [$old_link->id]);

        return Response::found($from);
    }
}
