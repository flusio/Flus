<?php

namespace App\models;

use Minz\Database;

/**
 * The content of the error returned when fetching a link (e.g. the content of
 * the HTTP response).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'fetch_errors')]
class FetchError
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $link_id;

    #[Database\Column]
    public string $content;

    /**
     * Save the content of the fetch error of the given link, replacing the
     * previous one if any.
     */
    public static function store(Link $link, string $content): void
    {
        $sql = <<<SQL
            INSERT INTO fetch_errors (created_at, link_id, content)
            VALUES (:created_at, :link_id, :content)
            ON CONFLICT (link_id) DO UPDATE SET
                created_at = excluded.created_at,
                content = excluded.content
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':created_at' => \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT),
            ':link_id' => $link->id,
            ':content' => $content,
        ]);
    }
}
