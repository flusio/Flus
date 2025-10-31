<?php

namespace App\controllers\links;

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
     * Mark a link as read and remove it from bookmarks.
     *
     * @request_param string id
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
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the link.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'view', $link);

        $from = utils\RequestHelper::from($request);

        $form = new forms\links\MarkLinkAsRead();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link = $user->obtainLink($link);

        if (!$link->isPersisted()) {
            $link->setSourceFrom($from);
            $link->save();
        }

        $user->markAsRead($link);

        return Response::found($from);
    }

    /**
     * Remove a link from news and add it to bookmarks.
     *
     * @request_param string id
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
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the link.
     */
    public function later(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'view', $link);

        $from = utils\RequestHelper::from($request);

        $form = new forms\links\MarkLinkAsReadLater();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link = $user->obtainLink($link);

        if (!$link->isPersisted()) {
            $link->setSourceFrom($from);
            $link->save();
        }

        $user->markAsReadLater($link);

        return Response::found($from);
    }

    /**
     * Remove a link from news and bookmarks and add it to the never list.
     *
     * @request_param string id
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
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the link.
     */
    public function never(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'view', $link);

        $from = utils\RequestHelper::from($request);

        $form = new forms\links\MarkLinkAsNever();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link = $user->obtainLink($link);
        if (!$link->isPersisted()) {
            $link->save();
        }

        $user->removeFromJournal($link);

        return Response::found($from);
    }

    /**
     * Mark a link as unread by removing it from read list.
     *
     * @request_param string id
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
     *     If the link doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the link.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $link = models\Link::requireFromRequest($request);

        auth\Access::require($user, 'update', $link);

        $from = utils\RequestHelper::from($request);

        $form = new forms\links\MarkLinkAsUnread();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        models\LinkToCollection::markAsUnread($user, [$link->id]);

        return Response::found($from);
    }
}
