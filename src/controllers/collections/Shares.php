<?php

namespace flusio\controllers\collections;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

class Shares
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
    public function index($request)
    {
        $current_user = auth\CurrentUser::get();
        $from = $request->param('from');
        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $collection_id = $request->param('id');
        $collection = models\Collection::find($collection_id);

        $can_update = auth\CollectionsAccess::canUpdate($current_user, $collection);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('collections/shares/index.phtml', [
            'collection' => $collection,
            'from' => $from,
            'type' => 'read',
            'user_id' => '',
        ]);
    }

    /**
     * @request_param string id
     * @request_param string user_id
     * @request_param string type
     * @request_param string csrf
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn’t exist or is inaccessible
     * @response 400
     *     If user_id is the same as the current user id, or user_id doesn't
     *     exist, or collection is already shared with user_id, or type is
     *     invalid
     * @response 302 :from
     *     On success
     */
    public function create($request)
    {
        $current_user = auth\CurrentUser::get();
        $from = $request->param('from');
        if (!$current_user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $collection_id = $request->param('id');
        $user_id = $request->param('user_id');
        $type = $request->param('type');
        $csrf = $request->param('csrf');

        $collection = models\Collection::find($collection_id);
        $can_update = auth\CollectionsAccess::canUpdate($current_user, $collection);
        if (!$can_update) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\CSRF::validate($csrf)) {
            return Response::badRequest('collections/shares/index.phtml', [
                'collection' => $collection,
                'from' => $from,
                'type' => $type,
                'user_id' => $user_id,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        if ($current_user->id === $user_id) {
            return Response::badRequest('collections/shares/index.phtml', [
                'collection' => $collection,
                'from' => $from,
                'type' => $type,
                'user_id' => $user_id,
                'errors' => [
                    'user_id' => _('You can’t share access with yourself.'),
                ],
            ]);
        }

        $support_user = models\User::supportUser();
        if (
            !models\User::exists($user_id) ||
            $support_user->id === $user_id
        ) {
            return Response::badRequest('collections/shares/index.phtml', [
                'collection' => $collection,
                'from' => $from,
                'type' => $type,
                'user_id' => $user_id,
                'errors' => [
                    'user_id' => _('This user doesn’t exist.'),
                ],
            ]);
        }

        $existing_collection_share = models\CollectionShare::findBy([
            'collection_id' => $collection->id,
            'user_id' => $user_id,
        ]);
        if ($existing_collection_share) {
            return Response::badRequest('collections/shares/index.phtml', [
                'collection' => $collection,
                'from' => $from,
                'type' => $type,
                'user_id' => $user_id,
                'errors' => [
                    'user_id' => _('The collection is already shared with this user.'),
                ],
            ]);
        }

        $collection_share = models\CollectionShare::init($user_id, $collection->id, $type);
        $errors = $collection_share->validate();
        if (isset($errors['type'])) {
            // type doesn't have a dedicated HTML field yet, so we want to
            // display its errors in the "global" alert.
            // Since, in theory, validate() only return errors concerning the
            // type property, we can handle it this way.
            return Response::badRequest('collections/shares/index.phtml', [
                'collection' => $collection,
                'from' => $from,
                'type' => $type,
                'user_id' => $user_id,
                'error' => $errors['type'],
            ]);
        }

        $collection_share->save();

        return Response::found($from);
    }
}
