<?php

namespace App\models\users;

use App\models\Subscription;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * Add methods to manage the password and the access of the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Authenticable
{
    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The password is required.'),
    )]
    public string $password_hash = '';

    #[Database\Column]
    public ?string $reset_token;

    /**
     * Set the user password.
     */
    public function setPassword(string $password): void
    {
        $this->password_hash = self::passwordHash($password);
    }

    /**
     * Change the user password if $password is not empty.
     */
    public function changePassword(string $password): void
    {
        if ($password) {
            $this->setPassword($password);
        }
    }

    /**
     * Return true if the password matches the hash, false otherwise.
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Return whether the user should be blocked or not (email not validated or
     * subscription overdue)
     */
    public function isBlocked(): bool
    {
        $must_validate = $this->mustValidateEmail();
        $must_renew = (
            \App\Configuration::areSubscriptionsEnabled() &&
            (new Subscription($this))->isOverdue()
        );
        return $must_validate || $must_renew;
    }

    /**
     * Return a password hash. If password is empty, password_hash will be empty as well.
     */
    public static function passwordHash(string $password): string
    {
        if ($password) {
            return password_hash($password, PASSWORD_BCRYPT, [
                'cost' => \App\Configuration::$application['password_hash_cost'],
            ]);
        } else {
            return '';
        }
    }
}
