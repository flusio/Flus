<?php

namespace App\controllers\sources;

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
class Follows extends BaseController
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
     *     If the source doesn't exist or is not followed by the user.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the source.
     */
    public function edit(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $source = models\Source::requireFromRequest($request);

        auth\Access::require($user, 'view', $source);

        $followed_collection = $user->followedCollection($source);

        if (!$followed_collection) {
            throw new \Minz\Errors\MissingRecordError('The user does not follow the source.');
        }

        $form = new forms\sources\EditFollow(model: $followed_collection, options: [
            'user' => $user,
        ]);

        return Response::ok('sources/follows/edit.html.twig', [
            'source' => $source,
            'form' => $form,
        ]);
    }

    /**
     * Update the subscription to the given source.
     *
     * @request_param string id
     * @request_param string time_filter
     * @request_param string[] stream_ids
     * @request_param string new_stream_name
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
     *     If the source doesn't exist or is not followed by the user.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the source.
     */
    public function update(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $source = models\Source::requireFromRequest($request);

        auth\Access::require($user, 'view', $source);

        $followed_collection = $user->followedCollection($source);

        if (!$followed_collection) {
            throw new \Minz\Errors\MissingRecordError('The user does not follow the source.');
        }

        $form = new forms\sources\EditFollow(model: $followed_collection, options: [
            'user' => $user,
        ]);

        $form->handleRequest($request);

        if (!$form->validate()) {
            return Response::badRequest('sources/follows/edit.html.twig', [
                'source' => $source,
                'form' => $form,
            ]);
        }

        $followed_collection = $form->model();
        $followed_collection->save();

        $streams = $form->selectedStreams();

        $named_stream = $form->namedStream();
        if ($named_stream) {
            if (!$named_stream->isPersisted()) {
                $named_stream->save();
            }

            $streams[] = $named_stream;
        }

        $followed_collection->setStreams($streams);

        return Response::found(utils\RequestHelper::from($request));
    }

    /**
     * Make the current user following the given source.
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
     *     If the source doesn't exist.
     * @throws auth\AccessDeniedError
     *     If the user cannot view the source.
     */
    public function create(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $source = models\Source::requireFromRequest($request);

        auth\Access::require($user, 'view', $source);

        $from = utils\RequestHelper::from($request);

        $form = new forms\sources\Follow();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $is_following = $user->isFollowing($source);
        if (!$is_following) {
            $user->follow($source);
        }

        return Response::found($from);
    }

    /**
     * Make the current user unfollowing the given source.
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
     *     If the source doesn't exist.
     */
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();
        $source = models\Source::requireFromRequest($request);

        $from = utils\RequestHelper::from($request);

        $form = new forms\sources\Unfollow();
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $is_following = $user->isFollowing($source);
        if ($is_following) {
            $user->unfollow($source);
        }

        return Response::found($from);
    }

    /**
     * Make the current user unfollowing the selected sources.
     *
     * @request_param string[] source_ids
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
     */
    public function deleteSelected(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $from = utils\RequestHelper::from($request);

        $form = new forms\sources\UnfollowSelected(options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base'));
            return Response::found($from);
        }

        $user->unfollowAll($form->selectedSources());

        return Response::found($from);
    }

    /**
     * Update the time filter of the selected sources.
     *
     * @request_param string[] source_ids
     * @request_param string time_filter
     * @request_param string csrf_token
     *
     * @response 302 :from
     * @flash error
     *     If the CSRF token or the time filter is invalid.
     * @response 302 :from
     *     On success.
     *
     * @throws auth\MissingCurrentUserError
     *     If the user is not connected.
     */
    public function updateSelectedTimeFilter(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $from = utils\RequestHelper::from($request);

        $form = new forms\sources\UpdateSelectedTimeFilter(options: [
            'user' => $user,
        ]);
        $form->handleRequest($request);

        if (!$form->validate()) {
            utils\Notification::error($form->error('@base') ?: $form->error('time_filter'));
            return Response::found($from);
        }

        models\FollowedCollection::updateTimeFilter($user, $form->selectedSources(), $form->time_filter);

        return Response::found($from);
    }
}
