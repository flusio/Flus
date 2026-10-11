<?php

namespace App\http;

use Minz\Database;

/**
 * Represent an error returned by an HTTP request made to an external website.
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
    public string $url;

    #[Database\Column]
    public string $type;

    #[Database\Column]
    public int $code;

    #[Database\Column]
    public string $content;

    /**
     * Create an error in DB for the given URL.
     */
    public static function log(string $url, string $type, int $code, string $content): void
    {
        $fetch_error = new self();

        $fetch_error->url = $url;
        $fetch_error->type = $type;
        $fetch_error->code = $code;
        // PostgreSQL doesn't accept null bytes in text columns, while the
        // content of a response can be binary.
        $fetch_error->content = str_replace("\0", '', $content);

        $fetch_error->save();
    }

    /**
     * Delete errors older than the given date
     */
    public static function deleteOlderThan(\DateTimeImmutable $date): bool
    {
        $sql = <<<SQL
            DELETE FROM fetch_errors
            WHERE created_at < ?
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute([
            $date->format(Database\Column::DATETIME_FORMAT),
        ]);
    }
}
