<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Profiles
{
    /**
     * Show the public profile page of a user.
     *
     * @request_param string id
     *
     * @response 404
     *    If the requested profile doesn’t exist or is associated to the
     *    support user.
     * @response 200
     *    On success
     */
    public function show($request)
    {
        $user_id = $request->param('id');
        $user = models\User::find($user_id);
        if (!$user || $user->isSupportUser()) {
            return Response::notFound('not_found.phtml');
        }

        $current_user = auth\CurrentUser::get();
        $is_current_user_profile = $current_user && $current_user->id === $user->id;
        $count_hidden_links = $is_current_user_profile;
        $collections = $user->publicCollections($count_hidden_links);
        models\Collection::sort($collections, utils\Locale::currentLocale());

        return Response::ok('profiles/show.phtml', [
            'user' => $user,
            'collections' => $collections,
            'is_current_user_profile' => $is_current_user_profile,
        ]);
    }
}
