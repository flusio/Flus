<?php

namespace App\controllers\links;

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
class Repairing extends BaseController
{
    /**
     * Show the page to repair a link (change URL and resynchronize it).
     *
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function new(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'update', $link);

        $form = new forms\links\RepairLink([
            'url' => $link->url,
            'force_sync' => $link->title === $link->url,
        ]);

        return Response::ok('links/repairing/new.phtml', [
            'link' => $link,
            'form' => $form,
        ]);
    }

    /**
     * Repair a link (change URL and resynchronize it).
     *
     * @request_param string id
     * @request_param string url
     * @request_param boolean force_sync
     * @request_param string csrf_token
     *
     * @response 404
     *     If the link cannot be updated by the current user.
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'update', $link);

        $form = new forms\links\RepairLink();
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('links/repairing/new.phtml', [
                'link' => $link,
                'form' => $form,
            ]);
        }

        $old_link = models\Link::copy($link, $user->id);

        $link->url = $form->url;

        $link_fetcher_service = new services\LinkFetcher([
            'http_timeout' => 10,
            'ignore_rate_limit' => true,
            'force_sync' => $form->force_sync,
        ]);
        $link_fetcher_service->fetch($link);

        // Add the old link to the never list. It avoids to a link coming from
        // the news to reappear.
        $old_link->save();
        $user->removeFromJournal($old_link);

        return Response::found(utils\RequestHelper::from($request));
    }
}
