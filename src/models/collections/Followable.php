<?php

namespace App\models\collections;

use App\models\FollowedCollection;
use App\models\User;
use Minz\Database;

/**
 * Add the relation between a collection and the users who follow it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Followable
{
    /**
     * Return the follow of the given user on this collection, or null if the
     * user doesn't follow it.
     */
    public function followByUser(User $user): ?FollowedCollection
    {
        return $user->followedCollection($this);
    }

    /**
     * Return the time filter applied by the given user to this collection, or
     * null if the user doesn't follow it.
     */
    public function timeFilterByUser(User $user): ?string
    {
        return $this->followByUser($user)?->time_filter;
    }

    /**
     * Return the name of the collection as given by the user.
     *
     * It defaults to the collection's name if the user didn't rename it.
     */
    public function nameByUser(?User $user): string
    {
        if (!$user) {
            return $this->name();
        }

        $name_by_user = $this->followByUser($user)?->name;
        return $name_by_user ?: $this->name();
    }

    /**
     * Return the image filename of the collection as given by the user.
     *
     * It defaults to the collection's image if the user didn't change it.
     */
    public function imageFilenameByUser(?User $user): ?string
    {
        if (!$user) {
            return $this->image_filename;
        }

        $image_filename_by_user = $this->followByUser($user)?->image_filename;
        return $image_filename_by_user ?: $this->image_filename;
    }

    /**
     * Return whether the given user follows this collection.
     */
    public function isFollowedBy(User $user): bool
    {
        return $this->followByUser($user) !== null;
    }

    /**
     * List the sources followed by the given user.
     *
     * @return self[]
     */
    public static function listSourcesByUser(User $user): array
    {
        $parameters = [
            ':user_id' => $user->id,
        ];

        $visibility_clause = self::buildVisibilityClause($user);

        $sql = <<<SQL
            SELECT c.*
            FROM collections c, followed_collections fc

            WHERE fc.collection_id = c.id
            AND fc.user_id = :user_id

            {$visibility_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
