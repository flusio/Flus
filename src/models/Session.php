<?php

namespace flusio\models;

use Minz\Database;
use flusio\utils;

/**
 * Represent a user login session.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'sessions')]
class Session
{
    use dao\Session;
    use Database\Recordable;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

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

    public function __construct(string $user_agent, string $ip)
    {
        $this->id = \Minz\Random::hex(32);
        $this->name = utils\Browser::format($user_agent);
        if (\Minz\Configuration::$application['demo']) {
            $this->ip = 'unknown';
        } else {
            $this->ip = utils\Ip::mask($ip);
        }
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
}
