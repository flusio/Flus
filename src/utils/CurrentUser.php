<?php

namespace flusio\utils;

use flusio\models;

/**
 * An utility class to help to manipulate the current user (i.e. the one who is
 * connected if any).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CurrentUser
{
    /** @var \flusio\models\User */
    private static $instance;

    /**
     * Load a User from the database by the id stored in the session.
     *
     * The User model is stored in the static $instance variable to avoid
     * useless multiple calls to the database.
     *
     * If the session token doesn't exist, has expired or is invalid, null is
     * returned.
     *
     * @return \flusio\models\User|null
     */
    public static function get()
    {
        if (!isset($_SESSION['current_session_token'])) {
            // Not logged in, meh.
            return null;
        }

        if (self::$instance !== null) {
            // Oh, it's you again?
            return self::$instance;
        }

        // Let's load the user from the database
        $current_user = models\User::daoToModel('findBySessionToken', $_SESSION['current_session_token']);
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
     *
     * @return \flusio\models\Session|null
     */
    public static function session()
    {
        if (isset($_SESSION['current_session_token'])) {
            return models\Session::findBy([
                'token' => $_SESSION['current_session_token'],
            ]);
        } else {
            return null;
        }
    }

    /**
     * Reset the current instance of User and reaload it from the database
     *
     * @return \flusio\models\User
     */
    public static function reload()
    {
        self::$instance = null;
        return self::get();
    }

    /**
     * Save the given session token in session and reset the instance.
     *
     * @param string $token
     */
    public static function setSessionToken($token)
    {
        $_SESSION['current_session_token'] = $token;
        self::$instance = null;
    }

    /**
     * Unset the user id in session and reset the instance.
     */
    public static function reset()
    {
        unset($_SESSION['current_session_token']);
        self::$instance = null;
    }

    /**
     * Return the current session token from $_SESSION.
     *
     * @return string|null
     */
    public static function sessionToken()
    {
        if (isset($_SESSION['current_session_token'])) {
            return $_SESSION['current_session_token'];
        } else {
            return null;
        }
    }
}
