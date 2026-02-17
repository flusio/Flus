<?php

namespace App\controllers\collections;

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
     * Mark links of the collection as read and remove them from bookmarks.
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
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'view', $collection);

        $from = utils\RequestHelper::from($request);

        $form = new forms\collections\MarkCollectionAsRead(options: [
            'collection' => $collection,
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
     * Remove links of the collection from news and add them to bookmarks.
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
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     */
    public function later(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'view', $collection);

        $from = utils\RequestHelper::from($request);

        $form = new forms\collections\MarkCollectionAsReadLater(options: [
            'collection' => $collection,
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
     * Remove links of the collection from news and bookmarks and add them to the never list.
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
     *     If the collection doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the collection.
     */
    public function never(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $collection = models\Collection::requireFromRequest($request);

        auth\Access::require($user, 'view', $collection);

        $from = utils\RequestHelper::from($request);

        $form = new forms\collections\MarkCollectionAsNever(options: [
            'collection' => $collection,
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $links = $form->links();

        $user->removeFromJournal($links);

        return Response::found($from);
    }
}
