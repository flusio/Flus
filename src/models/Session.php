<?php

namespace App\models;

use Minz\Database;
use App\utils;

/**
 * Represent a user login session.
 *
 * @phpstan-type Scope value-of<Session::SCOPES>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'sessions')]
class Session
{
    use dao\Session;
    use Database\Recordable;
    use Database\Resource;

    public const SCOPES = ['browser', 'api'];

    /**
     * Duration of inactivity after which a session expires, by scope.
     */
    public const INACTIVITY_DURATIONS = [
        'browser' => [2, 'weeks'],
        'api' => [1, 'month'],
    ];

    /**
     * Maximum lifetime of a session since its creation, whatever its activity.
     */
    public const MAX_LIFETIME = [1, 'year'];

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    /** @var Scope */
    #[Database\Column]
    public string $scope;

    #[Database\Column]
    public ?\DateTimeImmutable $confirmed_password_at;

    #[Database\Column]
    public string $name;

    #[Database\Column]
    public string $ip;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $token;

    /**
     * @param Scope $scope
     */
    public function __construct(User $user, Token $token, string $scope, string $name, string $ip)
    {
        $this->id = \Minz\Random::hex(32);

        $this->user_id = $user->id;
        $this->token = $token->token;
        $this->scope = $scope;
        $this->name = mb_substr(trim($name), 0, 50);
        $this->ip = $ip;
        $this->confirmed_password_at = null;
    }

    /**
     * Confirm the password for the current session.
     */
    public function confirmPassword(): void
    {
        $this->confirmed_password_at = \Minz\Time::now();
        $this->save();
    }

    /**
     * Return wheter the user confirmed its password within the last 15 minutes.
     */
    public function isPasswordConfirmed(): bool
    {
        if (!$this->confirmed_password_at) {
            return false;
        }

        return $this->confirmed_password_at >= \Minz\Time::ago(15, 'minutes');
    }

    public function user(): User
    {
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("Session #{$this->id} has invalid user.");
        }

        return $user;
    }

    public function token(): Token
    {
        $token = Token::find($this->token);

        if (!$token) {
            throw new \Exception("Session #{$this->id} has invalid token.");
        }

        return $token;
    }

    public function isValid(): bool
    {
        return $this->token()->isValid();
    }

    /**
     * Return the approximate date of the last activity of the session.
     *
     * The date is derived from the expiration of the token which is renewed
     * on use (see renew()), so it is accurate to within a day. It is capped
     * to now since the expiration cannot exceed the maximum lifetime of the
     * session.
     */
    public function lastActivityAt(): \DateTimeImmutable
    {
        [$number, $unit] = self::INACTIVITY_DURATIONS[$this->scope];
        $last_activity_at = $this->token()->expired_at->modify("-{$number} {$unit}");

        $now = \Minz\Time::now();
        if ($last_activity_at > $now) {
            $last_activity_at = $now;
        }

        return $last_activity_at;
    }

    /**
     * Extend the expiration of the token to keep the session alive while it
     * is used.
     *
     * The expiration is postponed by the inactivity duration of the scope,
     * but never later than the maximum lifetime of the session. To avoid
     * saving the token at each request, it is saved only if the expiration
     * gains at least one day.
     *
     * Return true if the expiration changed, false otherwise.
     */
    public function renew(): bool
    {
        $token = $this->token();

        [$number, $unit] = self::INACTIVITY_DURATIONS[$this->scope];
        $expired_at = \Minz\Time::fromNow($number, $unit);

        [$number, $unit] = self::MAX_LIFETIME;
        $max_expired_at = $this->created_at->modify("+{$number} {$unit}");

        if ($expired_at > $max_expired_at) {
            $expired_at = $max_expired_at;
        }

        if ($expired_at <= $token->expired_at->modify('+1 day')) {
            return false;
        }

        $token->expired_at = $expired_at;
        $token->save();

        return true;
    }
}
