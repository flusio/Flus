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
     * @return \flusio\models\User
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
        $user_dao = new models\dao\User();
        $current_user_values = $user_dao->findBySessionToken($_SESSION['current_session_token']);
        if (!$current_user_values) {
            // The user doesn't exist… what are you trying to do evil user?
            return null;
        }

        self::$instance = new models\User($current_user_values);
        return self::$instance;
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
}
