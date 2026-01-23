<?php

namespace App\controllers;

use App\auth;
use App\forms;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Groups extends BaseController
{
    /**
     * @request_param string id
     *
     * @response 200
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the group doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the group.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $group = models\Group::requireFromRequest($request);

        auth\Access::require($user, 'update', $group);

        $form = new forms\groups\Group(model: $group);

        return Response::ok('groups/edit.html.twig', [
            'group' => $group,
            'form' => $form,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string name
     * @request_param string csrf_token
     *
     * @response 400
     *     If at least one of the parameters is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     * @throws \Minz\Errors\MissingRecordError
     *     If the group doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot update the group.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $group = models\Group::requireFromRequest($request);

        auth\Access::require($user, 'update', $group);

        $form = new forms\groups\Group(model: $group);
        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('groups/edit.html.twig', [
                'group' => $group,
                'form' => $form,
            ]);
        }

        $group = $form->model();
        $group->save();

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
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
     *     If the group doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot delete the group.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $group = models\Group::requireFromRequest($request);

        auth\Access::require($user, 'delete', $group);

        $from = utils\RequestHelper::from($request);

        $form = new forms\groups\DeleteGroup();
        $form->handleRequest($request);

        if (!$form->validate()) {
            \Minz\Flash::set('error', $form->error('@base'));
            return Response::found($from);
        }

        $group->remove();

        return Response::found($from);
    }
}
