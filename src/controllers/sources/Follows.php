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
    public function delete(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $from = utils\RequestHelper::from($request);

        $form = new forms\sources\Unfollow(options: [
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
    public function updateTimeFilter(Request $request): Response
    {
        $user = auth\CurrentUser::require();

        $from = utils\RequestHelper::from($request);

        $form = new forms\sources\UpdateTimeFilter(options: [
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
