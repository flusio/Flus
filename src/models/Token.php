<?php

namespace flusio\models;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'tokens', primary_key: 'token')]
class Token
{
    use dao\Token;
    use Database\Recordable;

    #[Database\Column]
    public string $token;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public \DateTimeImmutable $expired_at;

    #[Database\Column]
    public ?\DateTimeImmutable $invalidated_at;

    /**
     * Initialize a token valid for a certain amount of time.
     *
     * @see \Minz\Time
     */
    public function __construct(int $number, string $duration, int $length = 64)
    {
        $this->token = \Minz\Random::hex($length);
        $this->expired_at = \Minz\Time::fromNow($number, $duration);
    }

    /**
     * Return whether the token has expired.
     */
    public function hasExpired(): bool
    {
        return \Minz\Time::now() >= $this->expired_at;
    }

    /**
     * Return whether the token has been invalidated.
     */
    public function isInvalidated(): bool
    {
        return $this->invalidated_at !== null;
    }

    /**
     * Return whether the token is valid (i.e. not expired and not invalidated)
     */
    public function isValid(): bool
    {
        return !$this->hasExpired() && !$this->isInvalidated();
    }

    /**
     * Return wheter the token is going to expire in the next $number of $units.
     *
     * @see \Minz\Time
     */
    public function expiresIn(int $number, string $unit): bool
    {
        return \Minz\Time::fromNow($number, $unit) >= $this->expired_at;
    }
}
