<?php

namespace App\models\link_lists;

use App\auth;
use App\models\Follow;
use App\models\Source;
use App\models\User;
use Minz\Database;

/**
 * Add the relation between a source and the users who follow it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Followable
{
    /**
     * Return the follow of the given user on this source, or null if the
     * user doesn't follow it.
     */
    public function findFollowBy(User $user): ?Follow
    {
        return $user->findFollow($this);
    }

    /**
     * Return the time filter applied by the given user to this source, or
     * null if the user doesn't follow it.
     */
    public function timeFilterByUser(User $user): ?string
    {
        return $this->findFollowBy($user)?->time_filter;
    }

    /**
     * Return the name of the source as given by the user.
     *
     * It defaults to the source's name if the user didn't rename it.
     */
    public function nameByUser(?User $user): string
    {
        if (!$user) {
            return $this->name();
        }

        $name_by_user = $this->findFollowBy($user)?->name;
        return $name_by_user ?: $this->name();
    }

    /**
     * Return the image filename of the source as given by the user.
     *
     * It defaults to the source's image if the user didn't change it.
     */
    public function imageFilenameByUser(?User $user): ?string
    {
        if (!$user) {
            return $this->image_filename;
        }

        $image_filename_by_user = $this->findFollowBy($user)?->image_filename;
        return $image_filename_by_user ?: $this->image_filename;
    }

    /**
     * Return whether the given user follows this source.
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->findFollowBy($user) !== null;
    }

    /**
     * List the sources followed by the given user.
     *
     * @return self[]
     */
    public static function listFollowedByUser(User $user): array
    {
        [$visibility_clause, $visibility_parameters] = auth\LinkListsAccess::viewQuery($user, 'c');

        $parameters = array_merge([
            ':user_id' => $user->id,
        ], $visibility_parameters);

        $sql = <<<SQL
            SELECT c.*
            FROM collections c, followed_collections fc

            WHERE fc.collection_id = c.id
            AND fc.user_id = :user_id
            AND {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return Source::fromDatabaseRows($statement->fetchAll());
    }
}
