<?php

namespace App\controllers;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Groups extends BaseController
{
    /**
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the group doesn't exist or is inaccessible
     * @response 200
     *     on success
     */
    public function edit(Request $request): Response
    {
        $group_id = $request->param('id', '');
        $from = $request->param('from', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $group = models\Group::find($group_id);

        if ($group && auth\GroupsAccess::canUpdate($user, $group)) {
            return Response::ok('groups/edit.phtml', [
                'group' => $group,
                'name' => $group->name,
                'from' => $from,
                'name_max_length' => models\Group::NAME_MAX_LENGTH,
            ]);
        } else {
            return Response::notFound('not_found.phtml');
        }
    }

    /**
     * @request_param string id
     * @request_param string name
     * @request_param string from
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the group doesn't exist or is inaccessible
     * @response 400
     *     if the CSRF token is invalid, if the name is invalid or already used
     * @response 302 :from
     *     on success
     */
    public function update(Request $request): Response
    {
        $group_id = $request->param('id', '');
        $name = $request->param('name', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $group = models\Group::find($group_id);

        if (!$group || !auth\GroupsAccess::canUpdate($user, $group)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('groups/edit.phtml', [
                'group' => $group,
                'name' => $name,
                'from' => $from,
                'name_max_length' => models\Group::NAME_MAX_LENGTH,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $group->name = trim($name);

        $errors = $group->validate();
        if ($errors) {
            return Response::badRequest('groups/edit.phtml', [
                'group' => $group,
                'name' => $name,
                'from' => $from,
                'name_max_length' => models\Group::NAME_MAX_LENGTH,
                'errors' => $errors,
            ]);
        }

        $existing_group = models\Group::findBy([
            'user_id' => $user->id,
            'name' => $group->name,
        ]);
        if ($existing_group && $existing_group->id !== $group->id) {
            return Response::badRequest('groups/edit.phtml', [
                'group' => $group,
                'name' => $name,
                'from' => $from,
                'name_max_length' => models\Group::NAME_MAX_LENGTH,
                'errors' => [
                    'name' => _('You already have a group with this name.'),
                ],
            ]);
        }

        $group->save();

        return Response::found($from);
    }

    /**
     * @request_param string id
     * @request_param string from
     * @request_param string csrf
     *
     * @response 302 /login?redirect_to=:from
     *     if not connected
     * @response 404
     *     if the group doesn't exist or is inaccessible
     * @response 302 :from
     * @flash error
     *     if the CSRF token is invalid
     * @response 302 :from
     *     on success
     */
    public function delete(Request $request): Response
    {
        $group_id = $request->param('id', '');
        $from = $request->param('from', '');
        $csrf = $request->param('csrf', '');

        $user = $this->requireCurrentUser(redirect_after_login: $from);

        $group = models\Group::find($group_id);

        if (!$group || !auth\GroupsAccess::canDelete($user, $group)) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            \Minz\Flash::set('error', _('A security verification failed.'));
            return Response::found($from);
        }

        models\Group::delete($group->id);

        return Response::found($from);
    }
}
