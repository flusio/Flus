<?php

namespace App\auth;

use App\models;
use App\utils;
use Minz\Request;

/**
 * An utility class to help to manipulate the current user (i.e. the one who is
 * connected if any).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CurrentUser
{
    private static ?models\User $instance = null;

    private static ?models\Session $session = null;

    /**
     * Create a new session (valid for 1 month) connected to the given user.
     *
     * If a Request is passed, it is used to store some information about the
     * session (name and IP address).
     *
     * @throws \RuntimeException
     *     Raised if the user is the support user.
     */
    public static function createBrowserSession(models\User $user, ?Request $request = null): models\Session
    {
        if ($user->isSupportUser()) {
            \Minz\Log::error('Someone tried to log in with the support user');
            throw new \RuntimeException('Cannot log in with the support user');
        }

        $token = new models\Token(1, 'month');
        $token->save();

        if ($request) {
            $user_agent = $request->headers->getString('User-Agent', '');
            $session_name = utils\Browser::format($user_agent);

            if (\App\Configuration::$application['demo']) {
                $session_ip = 'unknown';
            } else {
                $session_ip = utils\Ip::mask($request->ip());
            }
        } else {
            $session_name = 'unknown';
            $session_ip = 'unknown';
        }

        $session = new models\Session($user, $token, $session_name, $session_ip);
        $session->save();

        self::$session = $session;
        self::$instance = $user;

        return $session;
    }

    /**
     * Delete the current session if any and reset the current user.
     */
    public static function deleteSession(): void
    {
        if (self::$session) {
            self::$session->remove();
        }

        self::$session = null;
        self::$instance = null;
    }

    /**
     * Authenticate a user using a session token.
     *
     * If the session token doesn't exist, has expired or is invalid, it
     * returns null.
     *
     * @throws \RuntimeException
     *     Raised if the user is the support user.
     */
    public static function authenticate(string $session_token): ?models\User
    {
        $session = models\Session::findByTokenId($session_token);

        if (!$session) {
            return null;
        }

        $user = $session->user();

        if ($user->isSupportUser()) {
            $session->remove();
            \Minz\Log::error('Someone tried to log in with the support user');
            throw new \RuntimeException('Cannot log in with the support user');
        }

        self::$session = $session;
        self::$instance = $user;

        return $user;
    }

    /**
     * Return the logged-in user if any.
     */
    public static function get(): ?models\User
    {
        return self::$instance;
    }

    /**
     * Return the current session if any.
     */
    public static function session(): ?models\Session
    {
        return self::$session;
    }
}
