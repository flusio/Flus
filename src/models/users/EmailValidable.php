<?php

namespace App\models\users;

use App\models\Token;
use Minz\Database;

/**
 * Add methods to manage the validation of the email of the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait EmailValidable
{
    #[Database\Column]
    public ?\DateTimeImmutable $validated_at = null;

    #[Database\Column]
    public ?string $validation_token = null;

    /**
     * Mark the email of the user as validated, and delete the validation
     * token if any.
     */
    public function validateEmail(): void
    {
        if ($this->validation_token) {
            Token::delete($this->validation_token);
            $this->validation_token = null;
        }

        $this->validated_at = \Minz\Time::now();
    }

    /**
     * Return whether the user must validate its email or not.
     *
     * Note they has 1 day to test the application before being forced to validate.
     */
    public function mustValidateEmail(): bool
    {
        return !$this->isEmailValidated() && $this->created_at < \Minz\Time::ago(1, 'day');
    }

    /**
     * Return whether the email of the user is validated or not.
     */
    public function isEmailValidated(): bool
    {
        return $this->validated_at !== null;
    }

    /**
     * Delete the users created before the given date who never validated
     * their email.
     */
    public static function deleteNotValidatedOlderThan(\DateTimeImmutable $since): bool
    {
        $sql = <<<SQL
            DELETE FROM users
            WHERE validated_at IS NULL
            AND created_at < :since
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        return $statement->execute([
            ':since' => $since->format(Database\Column::DATETIME_FORMAT),
        ]);
    }
}
