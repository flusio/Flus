<?php

namespace App\models;

use Minz\Database;

/**
 * Represent the subscription of a user.
 *
 * The subscription data are stored in the users table.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Subscription
{
    public function __construct(
        private readonly User $user,
    ) {
    }

    /**
     * Return whether the user has a subscription account or not.
     */
    public function hasAccount(): bool
    {
        return $this->user->subscription_account_id !== null;
    }

    /**
     * Return whether the user has a free subscription or not.
     */
    public function isExempted(): bool
    {
        return $this->user->subscription_expired_at->getTimestamp() === 0;
    }

    /**
     * Return whether the user subscription is overdue or not.
     */
    public function isOverdue(): bool
    {
        return (
            !$this->isExempted() &&
            \Minz\Time::now() > $this->user->subscription_expired_at
        );
    }

    /**
     * Return a SQL condition (and its parameters) matching the users whose
     * subscription is overdue (i.e. neither active nor exempted).
     *
     * @return array{string, array<string, string>}
     */
    public static function sqlOverdue(): array
    {
        $sql = <<<'SQL'
            (
                subscription_expired_at <= :subscription_now
                AND subscription_expired_at > :subscription_exempted_date
            )
        SQL;

        $exempted_date = new \DateTimeImmutable('@0');
        $parameters = [
            ':subscription_now' => \Minz\Time::now()->format(Database\Column::DATETIME_FORMAT),
            ':subscription_exempted_date' => $exempted_date->format(Database\Column::DATETIME_FORMAT),
        ];

        return [$sql, $parameters];
    }
}
