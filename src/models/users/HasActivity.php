<?php

namespace App\models\users;

use App\models\Subscription;
use Minz\Database;

/**
 * Add methods to track the activity of the users and delete the inactive ones.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasActivity
{
    #[Database\Column]
    public \DateTimeImmutable $last_activity_at;

    #[Database\Column]
    public ?\DateTimeImmutable $deletion_notified_at;

    /**
     * Change the last activity attribute and save the user if it changed.
     * Only the day is remembered to not track the user too much and to avoid
     * to save the user at each request.
     */
    public function refreshLastActivity(): bool
    {
        $changed = false;
        $today = \Minz\Time::relative('today');

        if ($this->last_activity_at != $today) {
            $this->last_activity_at = $today;
            $changed = true;
        }

        if ($this->deletion_notified_at !== null) {
            $this->deletion_notified_at = null;
            $changed = true;
        }

        if ($changed) {
            $this->save();
        }

        return $changed;
    }

    public function isInactive(int $months = 12): bool
    {
        return $this->last_activity_at < \Minz\Time::ago($months, 'months');
    }

    /**
     * Return the users that haven't be active since the given date.
     *
     * If $except_subscribed is true, the users with an active subscription are
     * not returned.
     *
     * @return self[]
     */
    public static function listInactiveAndNotNotified(
        \DateTimeImmutable $inactive_since,
        bool $except_subscribed = false,
    ): array {
        $sql = <<<'SQL'
            SELECT * FROM users
            WHERE last_activity_at <= :inactive_since
            AND deletion_notified_at IS NULL
        SQL;

        $parameters = [
            ':inactive_since' => $inactive_since->format(Database\Column::DATETIME_FORMAT),
        ];

        if ($except_subscribed) {
            [$overdue_condition, $overdue_parameters] = Subscription::sqlOverdue();
            $sql .= " AND {$overdue_condition}";
            $parameters = array_merge($parameters, $overdue_parameters);
        }

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * Delete the inactive users that have been notified about it.
     *
     * If $except_subscribed is true, the users with an active subscription are
     * not deleted.
     */
    public static function deleteInactiveAndNotified(
        \DateTimeImmutable $inactive_since,
        \DateTimeImmutable $notified_since,
        bool $except_subscribed = false,
    ): bool {
        $sql = <<<SQL
            DELETE FROM users
            WHERE last_activity_at <= :inactive_since
            AND deletion_notified_at <= :notified_since
        SQL;

        $parameters = [
            ':inactive_since' => $inactive_since->format(Database\Column::DATETIME_FORMAT),
            ':notified_since' => $notified_since->format(Database\Column::DATETIME_FORMAT),
        ];

        if ($except_subscribed) {
            [$overdue_condition, $overdue_parameters] = Subscription::sqlOverdue();
            $sql .= " AND {$overdue_condition}";
            $parameters = array_merge($parameters, $overdue_parameters);
        }

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute($parameters);
    }
}
