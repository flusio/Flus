<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * The owner of contents. An owner is either a user, or the system.
 *
 * @phpstan-import-type DatabaseCriteria from Database\Recordable
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'owners')]
#[Database\Inheritance(column: 'type', classes: [
    'system' => SystemOwner::class,
    'user' => UserOwner::class,
])]
abstract class Owner
{
    use Database\Recordable;
    use utils\Memoizer;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $type;

    /**
     * Return the owner matching the criteria, or insert the given one if
     * it doesn't exist yet.
     *
     * @param DatabaseCriteria $criteria
     */
    protected static function findOrInsert(array $criteria, self $new_owner): static
    {
        $owner = static::findBy($criteria);

        if ($owner) {
            return $owner;
        }

        // The owner is not inserted with save(), so we must set created_at
        // ourselves.
        $new_owner->created_at = \Minz\Time::now();

        $values = $new_owner->toDbValues();
        $columns = array_keys($values);

        $columns_clause = implode(', ', $columns);
        $values_placeholders = array_map(fn(string $column): string => ":{$column}", $columns);
        $values_clause = implode(', ', $values_placeholders);

        // The owner is inserted with "ON CONFLICT DO NOTHING" so concurrent
        // requests cannot create it twice.
        $sql = <<<SQL
            INSERT INTO owners ({$columns_clause})
            VALUES ({$values_clause})
            ON CONFLICT DO NOTHING
            RETURNING *
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);

        $row = $statement->fetch();

        if (is_array($row)) {
            return static::fromDatabaseRow($row);
        }

        // A concurrent request created the owner in the meantime.
        return static::requireBy($criteria);
    }
}
