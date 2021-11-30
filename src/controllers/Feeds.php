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

        $groups = models\Group::daoToList('listBy', ['user_id' => $user->id]);
        models\Group::sort($groups, $user->locale);

        $collections = models\Collection::daoToList('listForFeedsPage', $user->id);
        $collections_by_group_ids = [];
        $collections_no_group = [];
        foreach ($collections as $collection) {
            if ($collection->group_id) {
                $collections_by_group_ids[$collection->group_id][] = $collection;
            } else {
                $collections_no_group[] = $collection;
            }
        }

        foreach ($collections_by_group_ids as &$collections_of_group) {
            models\Collection::sort($collections_of_group, $user->locale);
        }
        models\Collection::sort($collections_no_group, $user->locale);

        return Response::ok('feeds/index.phtml', [
            'groups' => $groups,
            'collections_no_group' => $collections_no_group,
            'collections_by_group_ids' => $collections_by_group_ids,
        ]);
    }
}
