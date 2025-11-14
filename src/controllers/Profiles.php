<?php

namespace App\controllers;

use App\auth;
use App\models;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Profiles extends BaseController
{
    /**
     * Show the public profile page of a user.
     *
     * @request_param string id
     *
     * @response 404
     *    If the requested profile is associated to the support user.
     * @response 200
     *    On success.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the user doesn't exist.
     */
    public function show(Request $request): Response
    {
        $user = models\User::requireFromRequest($request);

        if ($user->isSupportUser()) {
            return Response::notFound('not_found.phtml');
        }

        $current_user = auth\CurrentUser::get();
        $is_current_user_profile = $current_user && $current_user->id === $user->id;

        $links = $user->links(['published_at', 'number_notes'], [
            'unshared' => false,
            'limit' => 6,
        ]);

        $collections = $user->collections(['number_links'], [
            'private' => false,
            'count_hidden' => $is_current_user_profile,
        ]);
        $collections = utils\Sorter::localeSort($collections, 'name');

        $shared_collections = [];
        if ($current_user) {
            $shared_collections = $user->sharedCollectionsTo($current_user->id, ['number_links']);
            $shared_collections = utils\Sorter::localeSort($shared_collections, 'name');
        }

        return Response::ok('profiles/show.phtml', [
            'user' => $user,
            'links' => $links,
            'collections' => $collections,
            'shared_collections' => $shared_collections,
            'is_current_user_profile' => $is_current_user_profile,
        ]);
    }
}
