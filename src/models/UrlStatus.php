<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table('url_statuses')]
class UrlStatus
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $url_hash;

    #[Database\Column]
    public ?\DateTimeImmutable $read_at = null;

    #[Database\Column]
    public ?\DateTimeImmutable $read_later_at = null;

    #[Database\Column]
    public ?\DateTimeImmutable $dismissed_at = null;

    /**
     * Mark the links as read for the user.
     *
     * @param Link|Link[] $links
     */
    public static function markAsRead(User $user, Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        if (!$links) {
            return;
        }

        $now = \Minz\Time::now();

        $values_as_question_marks = [];
        $values = [];

        foreach ($links as $link) {
            $values_as_question_marks[] = '(?, ?, ?, ?)';
            $values = array_merge($values, [
                $now->format(Database\Column::DATETIME_FORMAT),
                $user->id,
                $link->url_hash,
                $now->format(Database\Column::DATETIME_FORMAT),
            ]);
        }
        $values_placeholder = implode(", ", $values_as_question_marks);

        $sql = <<<SQL
            INSERT INTO url_statuses (created_at, user_id, url_hash, read_at)
            VALUES {$values_placeholder}
            ON CONFLICT (user_id, url_hash) DO UPDATE SET
                read_at = excluded.read_at,
                read_later_at = NULL
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);
    }

    /**
     * Unmark the links as read for the user.
     *
     * @param Link|Link[] $links
     */
    public static function unmarkAsRead(User $user, Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        if (!$links) {
            return;
        }

        $now = \Minz\Time::now();

        $values = [
            $user->id,
        ];
        $hashes_as_question_marks = [];

        foreach ($links as $link) {
            $hashes_as_question_marks[] = '?';
            $values[] = $link->url_hash;
        }
        $hashes_placeholder = implode(", ", $hashes_as_question_marks);

        $sql = <<<SQL
            UPDATE url_statuses
            SET read_at = NULL
            WHERE user_id = ?
            AND url_hash IN ({$hashes_placeholder})
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);
    }

    /**
     * Mark the links to read later for the user.
     *
     * @param Link|Link[] $links
     */
    public static function markAsReadLater(User $user, Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        if (!$links) {
            return;
        }

        $now = \Minz\Time::now();

        $values_as_question_marks = [];
        $values = [];

        foreach ($links as $link) {
            $values_as_question_marks[] = '(?, ?, ?, ?)';
            $values = array_merge($values, [
                $now->format(Database\Column::DATETIME_FORMAT),
                $user->id,
                $link->url_hash,
                $now->format(Database\Column::DATETIME_FORMAT),
            ]);
        }
        $values_placeholder = implode(", ", $values_as_question_marks);

        $sql = <<<SQL
            INSERT INTO url_statuses (created_at, user_id, url_hash, read_later_at)
            VALUES {$values_placeholder}
            ON CONFLICT (user_id, url_hash) DO UPDATE SET
                read_later_at = excluded.read_later_at
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);
    }

    /**
     * Mark the links as dismissed and remove them from the journal of the user.
     *
     * @param Link|Link[] $links
     */
    public static function markAsDismissed(User $user, Link|array $links): void
    {
        if ($links instanceof Link) {
            $links = [$links];
        }

        if (!$links) {
            return;
        }

        $now = \Minz\Time::now();

        $values_as_question_marks = [];
        $values = [];

        foreach ($links as $link) {
            $values_as_question_marks[] = '(?, ?, ?, ?)';
            $values = array_merge($values, [
                $now->format(Database\Column::DATETIME_FORMAT),
                $user->id,
                $link->url_hash,
                $now->format(Database\Column::DATETIME_FORMAT),
            ]);
        }
        $values_placeholder = implode(", ", $values_as_question_marks);

        $sql = <<<SQL
            INSERT INTO url_statuses (created_at, user_id, url_hash, dismissed_at)
            VALUES {$values_placeholder}
            ON CONFLICT (user_id, url_hash) DO UPDATE SET
                dismissed_at = excluded.dismissed_at
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($values);
    }
}
