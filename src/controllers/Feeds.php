<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\models;
use flusio\services;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Feeds
{
    /**
     * List the followed feeds/collections of the current user.
     *
     * @response 302 /login?redirect_to=/feeds
     *     if the user is not connected
     * @response 200
     */
    public function index($request)
    {
        $user = auth\CurrentUser::get();

        if (!$user) {
            return Response::redirect('login', ['redirect_to' => \Minz\Url::for('feeds')]);
        }

        $no_group_followed_collections = models\Collection::daoToList('listFollowedInGroup', $user->id, null);
        models\Collection::sort($no_group_followed_collections, $user->locale);

        $groups = models\Group::daoToList('listBy', ['user_id' => $user->id]);
        models\Group::sort($groups, $user->locale);

        return Response::ok('feeds/index.phtml', [
            'no_group_followed_collections' => $no_group_followed_collections,
            'groups' => $groups,
        ]);
    }
}
