<?php

namespace App\models\collections;

use App\models\User;
use Minz\Database;

/**
 * Add the relation between a collection and the user who owns it.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait OwnedByUser
{
    #[Database\Column]
    public ?string $user_id = null;

    /**
     * Return the owner of the collection.
     */
    public function owner(): ?User
    {
        return $this->memoize('owner', function (): ?User {
            if (!$this->user_id) {
                return null;
            }

            return User::find($this->user_id);
        });
    }

    /**
     * Set the owner of the collection without querying the database.
     *
     * @see \App\utils\OriginFormatter::preloadOrigins
     */
    public function preloadOwner(?User $owner): void
    {
        $this->memoizeValue('owner', $owner);
    }

    /**
     * Return the collections of the given user with its computed properties.
     *
     * @param string $user_id
     *     The user id that the collections must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     * @param array{
     *     'private'?: bool,
     *     'count_hidden'?: bool,
     * } $options
     *
     * Description of the options:
     * - private (default to true), indicates if private collections must be
     *   included. If private are excluded and number_links property is
     *   selected, empty public collections are not returned either.
     * - count_hidden (default to true), indicates if hidden links must be
     *   counted
     *
     * @return self[]
     */
    public static function listComputedByUserId(
        string $user_id,
        array $selected_computed_props,
        array $options = [],
    ): array {
        $default_options = [
            'private' => true,
            'count_hidden' => true,
        ];
        $options = array_merge($default_options, $options);

        $parameters = [
            ':user_id' => $user_id,
        ];

        $number_links_clause = '';
        $join_clause = '';
        $group_by_clause = '';
        if (in_array('number_links', $selected_computed_props)) {
            $number_links_clause = ', COUNT(lc.id) AS number_links';
            $join_clause = <<<SQL
                LEFT JOIN links_to_collections lc
                ON lc.collection_id = c.id
            SQL;
            $group_by_clause = 'GROUP BY c.id';

            if (!$options['count_hidden']) {
                $number_links_clause = ', COUNT(l.id) AS number_links';

                $join_clause .= <<<SQL
                    \n
                    LEFT JOIN links l
                    ON lc.link_id = l.id
                    AND l.is_hidden = false
                SQL;
            }
        }

        $private_clause = '';
        $non_empty_clause = '';
        if (!$options['private']) {
            $private_clause = 'AND c.is_public = true';

            if (in_array('number_links', $selected_computed_props)) {
                $non_empty_clause = 'HAVING COUNT(lc.id) > 0';

                if (!$options['count_hidden']) {
                    $non_empty_clause = 'HAVING COUNT(l.id) > 0';
                }
            }
        }

        $sql = <<<SQL
            SELECT
                c.*
                {$number_links_clause}
            FROM collections c

            {$join_clause}

            WHERE c.user_id = :user_id
            AND c.type = 'collection'

            {$private_clause}

            {$group_by_clause}

            {$non_empty_clause}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
