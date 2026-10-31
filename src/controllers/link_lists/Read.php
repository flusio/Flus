<?php

namespace App\controllers\link_lists;

use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Read extends BaseController
{
    /**
     * Mark links of the list as read.
     *
     * @request_param string id
     * @request_param date date
     * @request_param string source
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the list doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the list.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link_list = models\LinkList::requireFromRequest($request);

        auth\Access::require($user, 'view', $link_list);

        $from = utils\RequestHelper::from($request);

        $form = new forms\link_lists\MarkAsRead(options: [
            'link_list' => $link_list,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $links = $form->links();

        $user->markAsRead($links);

        return Response::found($from);
    }

    /**
     * Mark links of the list to be read later.
     *
     * @request_param string id
     * @request_param date date
     * @request_param string source
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the list doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the list.
     */
    public function later(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link_list = models\LinkList::requireFromRequest($request);

        auth\Access::require($user, 'view', $link_list);

        $from = utils\RequestHelper::from($request);

        $form = new forms\link_lists\MarkAsReadLater(options: [
            'link_list' => $link_list,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $links = $form->links();

        $user->markAsReadLater($links);

        return Response::found($from);
    }

    /**
     * Dismiss the links of the list.
     *
     * @request_param string id
     * @request_param date date
     * @request_param string source
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the list doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the list.
     */
    public function dismiss(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link_list = models\LinkList::requireFromRequest($request);

        auth\Access::require($user, 'view', $link_list);

        $from = utils\RequestHelper::from($request);

        $form = new forms\link_lists\MarkAsDismissed(options: [
            'link_list' => $link_list,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $links = $form->links(obtain_links: false);

        $user->markAsDismissed($links);

        return Response::found($from);
    }
}
