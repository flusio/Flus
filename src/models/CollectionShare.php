<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @phpstan-type ShareType value-of<self::VALID_TYPES>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'collection_shares')]
class CollectionShare
{
    use Database\Recordable;
    use utils\Memoizer;
    use Validable;

    public const VALID_TYPES = ['read', 'write'];

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $collection_id;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The type is required.'),
    )]
    #[Validable\Inclusion(
        in: self::VALID_TYPES,
        message: new Translatable('The type is invalid.'),
    )]
    public string $type;

    public function __construct(User $user, Collection $collection, string $type)
    {
        $this->user_id = $user->id;
        $this->collection_id = $collection->id;
        $this->type = $type;
    }

    /**
     * Return the user the collection is shared with.
     */
    public function user(): User
    {
        return $this->memoize('user', function (): User {
            return User::require($this->user_id);
        });
    }

    /**
     * Set the user of the share without querying the database.
     *
     * @see link_lists\Preloader::shares
     */
    public function preloadUser(User $user): void
    {
        $this->memoizeValue('user', $user);
    }

    /**
     * Return whether the link is shared with the given user (i.e. it is
     * attached to a shared collection).
     *
     * @param 'any'|'read'|'write' $access_type
     */
    public static function existsForUserAndLink(
        User $user,
        Link $link,
        string $access_type = 'any',
    ): bool {
        // we don't need the clause if access_type is 'any' (i.e. the type
        // doesn't matter) or 'read' (i.e. read access is included in write
        // access)
        $access_type_clause = '';
        if ($access_type === 'write') {
            $access_type_clause = "AND cs.type = 'write'";
        }

        $sql = <<<SQL
            SELECT EXISTS (
                SELECT 1
                FROM collection_shares cs, links_to_collections lc

                WHERE cs.collection_id = lc.collection_id
                AND cs.user_id = :user_id
                AND lc.link_id = :link_id

                {$access_type_clause}
            )
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
            ':link_id' => $link->id,
        ]);

        return (bool) $statement->fetchColumn();
    }

    /**
     * Return the ids of the users the given collections are shared with in
     * write access, indexed by the ids of these collections.
     *
     * The collections shared with nobody are absent from the returned array.
     *
     * @param Collection[] $collections
     *
     * @return array<string, string[]>
     */
    public static function listWriterIdsByCollections(array $collections): array
    {
        if (!$collections) {
            return [];
        }

        $collection_ids = array_column($collections, 'id');
        $ids_as_question_marks = array_fill(0, count($collection_ids), '?');
        $ids_as_question_marks = implode(', ', $ids_as_question_marks);

        $sql = <<<SQL
            SELECT cs.collection_id, cs.user_id
            FROM collection_shares cs

            WHERE cs.collection_id IN ({$ids_as_question_marks})
            AND cs.type = 'write'
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($collection_ids);

        $user_ids_by_collection_ids = [];

        foreach ($statement->fetchAll() as $row) {
            $user_ids_by_collection_ids[$row['collection_id']][] = $row['user_id'];
        }

        return $user_ids_by_collection_ids;
    }
}
