<?php

namespace App\cli;

use Minz\Request;
use Minz\Response;
use App\jobs;
use App\models;
use App\services;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Streams
{
    public function migrate(Request $request): Response
    {
        $user_id = $request->parameters->getString('user', '');
        $user = models\User::find($user_id);

        if (!$user) {
            return Response::text(400, "User {$user_id} is invalid.");
        }

        $followed_collections = models\FollowedCollection::listBy([
            'user_id' => $user->id,
        ]);

        $groups_by_ids = [];
        $collections_by_group_ids = [];

        foreach ($followed_collections as $followed_collection) {
            if (!$followed_collection->group_id) {
                continue;
            }

            if (isset($groups_by_ids[$followed_collection->group_id])) {
                $group = $groups_by_ids[$followed_collection->group_id];
            } else {
                $group = models\Group::find($followed_collection->group_id);
                $groups_by_ids[$group->id] = $group;
            }

            $collection = models\Collection::find($followed_collection->collection_id);

            if (!isset($collections_by_group_ids[$group->id])) {
                $collections_by_group_ids[$group->id] = [];
            }

            $collections_by_group_ids[$group->id][] = $collection;
        }

        foreach ($groups_by_ids as $group) {
            $stream = $user->initStream();
            $stream->name = $group->name;
            $stream->save();

            foreach ($collections_by_group_ids[$group->id] as $collection) {
                $stream->addSource($collection);
            }
        }

        return Response::text(200, 'Followed collections groups migrated to streams successfully.');
    }
}
