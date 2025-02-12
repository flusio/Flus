<?php

namespace App\auth;

use App\models;

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

    /**
     * Load a User from the database by the id stored in the session.
     *
     * The User model is stored in the static $instance variable to avoid
     * useless multiple calls to the database.
     *
     * If the session token doesn't exist, has expired or is invalid, null is
     * returned.
     */
    public static function get(): ?models\User
    {
        $session_token = self::sessionToken();

        if (!$session_token) {
            // Not logged in, meh.
            return null;
        }

        if (self::$instance !== null) {
            // Oh, it's you again?
            return self::$instance;
        }

        // Let's load the user from the database
        $current_user = models\User::findBySessionToken($session_token);

        if (!$current_user) {
            // The user doesn't exist… what are you trying to do evil user?
            return null;
        }

        self::$instance = $current_user;
        return self::$instance;
    }

    /**
     * Return the current session.
     *
     * Please note the token is not verified, so always check the user is
     * logged in with the `get()` method.
     */
    public static function session(): ?models\Session
    {
        $session_token = self::sessionToken();

        if ($session_token) {
            return models\Session::findBy([
                'token' => $session_token,
            ]);
        } else {
            return null;
        }
    }

    /**
     * Reset the current instance of User and reaload it from the database
     */
    public static function reload(): ?models\User
    {
        self::$instance = null;
        return self::get();
    }

    /**
     * Save the given session token in session and reset the instance.
     */
    public static function setSessionToken(string $token): void
    {
        $_SESSION['current_session_token'] = $token;
        self::$instance = null;
    }

    /**
     * Unset the user id in session and reset the instance.
     */
    public static function reset(): void
    {
        unset($_SESSION['current_session_token']);
        self::$instance = null;
    }

    /**
     * Return the current session token from $_SESSION.
     */
    public static function sessionToken(): ?string
    {
        $session_token = $_SESSION['current_session_token'] ?? null;

        if ($session_token === null || !is_string($session_token)) {
            return null;
        }

        return $session_token;
    }
}
