<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\auth;
use App\models;

class Filters
{
    /**
     * @request_param string id
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn’t exist or is not followed
     * @response 200
     *     On success
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from', '');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $collection_id = $request->param('id', '');
        $collection = models\Collection::find($collection_id);

        $can_view = $collection && auth\CollectionsAccess::canView($user, $collection);
        if (!$can_view) {
            return Response::notFound('not_found.phtml');
        }

        $followed_collection = models\FollowedCollection::findBy([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        if (!$followed_collection) {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('collections/filters/edit.phtml', [
            'collection' => $collection,
            'from' => $from,
            'time_filter' => $followed_collection->time_filter,
        ]);
    }

    /**
     * @request_param string id
     * @request_param string time_filter
     * @request_param string csrf
     * @request_param string from
     *
     * @response 302 /login?redirect_to=:from
     *     If not connected
     * @response 404
     *     If the collection doesn’t exist or is not followed
     * @response 400
     *     If CSRF or time_filter is invalid
     * @response 302 :from
     *     On success
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::get();
        $from = $request->param('from', '');
        if (!$user) {
            return Response::redirect('login', [
                'redirect_to' => $from,
            ]);
        }

        $time_filter = $request->param('time_filter', '');
        $collection_id = $request->param('id', '');
        $csrf = $request->param('csrf', '');

        $collection = models\Collection::find($collection_id);

        $can_view = $collection && auth\CollectionsAccess::canView($user, $collection);
        if (!$can_view) {
            return Response::notFound('not_found.phtml');
        }

        $followed_collection = models\FollowedCollection::findBy([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        if (!$followed_collection) {
            return Response::notFound('not_found.phtml');
        }

        if (!\Minz\Csrf::validate($csrf)) {
            return Response::badRequest('collections/filters/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'time_filter' => $time_filter,
                'error' => _('A security verification failed: you should retry to submit the form.'),
            ]);
        }

        $followed_collection->time_filter = $time_filter;
        $errors = $followed_collection->validate();
        if ($errors) {
            return Response::badRequest('collections/filters/edit.phtml', [
                'collection' => $collection,
                'from' => $from,
                'time_filter' => $time_filter,
                'errors' => $errors,
            ]);
        }

        $followed_collection->save();

        return Response::found($from);
    }
}
