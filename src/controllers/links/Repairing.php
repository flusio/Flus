<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\models;
use App\services;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Repairing extends BaseController
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
        $link_id = $request->parameters->getString('id', '');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

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
        $link_id = $request->parameters->getString('id', '');
        $url = $request->parameters->getString('url', '');
        $force_sync = $request->parameters->getBoolean('force_sync');
        $csrf = $request->parameters->getString('csrf', '');
        $from = $request->parameters->getString('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $link = models\Link::find($link_id);
        $can_update = $link && auth\LinksAccess::canUpdate($user, $link);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        if (!\App\Csrf::validate($csrf)) {
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

        if (!$link->validate()) {
            return Response::badRequest('links/repairing/new.phtml', [
                'link' => $link,
                'url' => $url,
                'url_cleared' => $url,
                'force_sync' => $force_sync,
                'from' => $from,
                'errors' => $link->errors(),
            ]);
        }

        $link_fetcher_service = new services\LinkFetcher([
            'http_timeout' => 10,
            'ignore_rate_limit' => true,
            'force_sync' => $force_sync,
        ]);
        $link_fetcher_service->fetch($link);

        // Add the old link to the never list. It avoids to a link coming from
        // the news to reappear.
        $old_link->save();
        $user->removeFromJournal($old_link);

        return Response::found($from);
    }
}
