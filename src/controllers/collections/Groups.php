<?php

namespace flusio\controllers\collections;

use Minz\Response;
use flusio\auth;
use flusio\models;

class Groups
{
    /**
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn’t exist or is inaccessible
     * @response 200
     *     On success
     */
    public function edit($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $collection_id = $request->param('id');
        $collection = models\Collection::find($collection_id);

        $can_view = auth\CollectionsAccess::canView($user, $collection);
        if (!$can_view) {
            return Response::notFound('not_found.phtml');
        }

        $can_update = auth\CollectionsAccess::canUpdate($user, $collection);
        $is_following = $user->isFollowing($collection->id);
        if (!$can_update && !$is_following) {
            return Response::notFound('not_found.phtml');
        }

        $existing_group = $collection->groupForUser($user->id);
        if ($existing_group) {
            $name = $existing_group->name;
        } else {
            $name = '';
        }

        $groups = models\Group::daoToList('listBy', [
            'user_id' => $user->id,
        ]);
        models\Group::sort($groups, $user->locale);

        return Response::ok('collections/groups/edit.phtml', [
            'collection' => $collection,
            'groups' => $groups,
            'from' => $from,
            'name' => $name,
            'name_max_length' => models\Group::NAME_MAX_LENGTH,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string name
     * @request_param string csrf
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn’t exist or is inaccessible
     * @response 400
     *     If CSRF or name is invalid
     * @response 302 :from
     *     On success
     */
    public function update($request)
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $name = $request->param('name', '');
        $collection_id = $request->param('id');
        $csrf = $request->param('csrf');

        $collection = models\Collection::find($collection_id);

        $can_view = auth\CollectionsAccess::canView($user, $collection);
        if (!$can_view) {
            return Response::notFound('not_found.phtml');
        }

        $can_update = auth\CollectionsAccess::canUpdate($user, $collection);
        $is_following = $user->isFollowing($collection->id);
        if (!$can_update && !$is_following) {
            return Response::notFound('not_found.phtml');
        }

        $groups = models\Group::daoToList('listBy', [
            'user_id' => $user->id,
        ]);
        models\Group::sort($groups, $user->locale);

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest('collections/groups/edit.phtml', [
                'collection' => $collection,
                'groups' => $groups,
                'from' => $from,
                'name' => $name,
                'name_max_length' => models\Group::NAME_MAX_LENGTH,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($name) {
            $group = models\Group::init($user->id, $name);

            $errors = $group->validate();
            if ($errors) {
                return Response::badRequest('collections/groups/edit.phtml', [
                    'collection' => $collection,
                    'groups' => $groups,
                    'from' => $from,
                    'name' => $name,
                    'name_max_length' => models\Group::NAME_MAX_LENGTH,
                    'errors' => $errors,
                ]);
            }

            $existing_group_key = array_search($group->name, array_column($groups, 'name'));
            if ($existing_group_key !== false) {
                $group = $groups[$existing_group_key];
            } else {
                $group->save();
            }

            $group_id = $group->id;
        } else {
            $group_id = null;
        }

        if ($can_update) {
            $collection->group_id = $group_id;
            $collection->save();
        } else {
            $followed_collection_dao = new models\dao\FollowedCollection();
            $db_followed_collection = $followed_collection_dao->findBy([
                'user_id' => $user->id,
                'collection_id' => $collection->id,
            ]);
            $followed_collection_dao->update($db_followed_collection['id'], [
                'group_id' => $group_id,
            ]);
        }

        return Response::found($from);
    }
}
