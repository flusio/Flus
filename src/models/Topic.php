<?php

namespace App\models;

use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'topics')]
class Topic
{
    use Database\Recordable;
    use Database\Resource;
    use Validable;

    public const LABEL_MAX_SIZE = 30;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The label is required.'),
    )]
    #[Validable\Length(
        max: self::LABEL_MAX_SIZE,
        message: new Translatable('The label must be less than {max} characters.'),
    )]
    public string $label;

    public function __construct(string $label)
    {
        $this->id = \Minz\Random::timebased();
        $this->label = trim($label);
    }

    /**
     * Return the number of public collections attached to this topic
     */
    public function countPublicCollections(): int
    {
        return Collection::countPublicByTopic($this);
    }

    /**
     * Return the list of topics attached to the given collection.
     *
     * @return self[]
     */
    public static function listByCollection(Collection $collection): array
    {
        $sql = <<<SQL
            SELECT t.*
            FROM topics t, collections_to_topics ct

            WHERE t.id = ct.topic_id
            AND ct.collection_id = :collection_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':collection_id' => $collection->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }
}
