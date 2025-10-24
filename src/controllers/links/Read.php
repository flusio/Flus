<?php

namespace App\controllers\links;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\controllers\BaseController;
use App\forms;
use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Read extends BaseController
{
    /**
     * Mark a link as read and remove it from bookmarks.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not accessible to the current user
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function create(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');
        $link_id = $request->parameters->getString('id', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $form = new forms\links\MarkLinkAsRead();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $link = $user->obtainLink($link);
        if (!$link->isPersisted()) {
            utils\SourceHelper::setLinkSource($link, $from);
            $link->save();
        }

        $user->markAsRead($link);

        return Response::found($from);
    }

    /**
     * Remove a link from news and add it to bookmarks.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not accessible to the current user
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function later(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');
        $link_id = $request->parameters->getString('id', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $form = new forms\links\MarkLinkAsReadLater();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        $link = $user->obtainLink($link);
        if (!$link->isPersisted()) {
            utils\SourceHelper::setLinkSource($link, $from);
            $link->save();
        }

        $user->markAsReadLater($link);

        return Response::found($from);
    }

    /**
     * Remove a link from news and bookmarks and add it to the never list.
     *
     * @request_param string csrf
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not accessible to the current user
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function never(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');
        $link_id = $request->parameters->getString('id', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $form = new forms\links\MarkLinkAsNever();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canView($user, $link)) {
            return Response::notFound('not_found.phtml');
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
     * @request_param string csrf
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the link doesn't exist, or is not accessible to the current user
     * @response 302 :from
     * @flash error
     *     if CSRF is invalid
     * @response 302 :from
     *     on success
     */
    public function delete(Request $request): Response
    {
        $from = $request->parameters->getString('from', '');
        $link_id = $request->parameters->getString('id', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $form = new forms\links\MarkLinkAsUnread();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $link = models\Link::find($link_id);
        if (!$link || !auth\LinksAccess::canUpdate($user, $link)) {
            return Response::notFound('not_found.phtml');
        }

        models\LinkToCollection::markAsUnread($user, [$link->id]);

        return Response::found($from);
    }
}
