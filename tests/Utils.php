<?php

namespace tests;

/**
 * Provide utility methods during tests.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Utils
{
    /**
     * Simulate a user who logs in.
     *
     * A User is created using a DatabaseFactory.
     *
     * @param array $user_values Values of the User to create (optional)
     *
     * @return \flusio\models\User
     */
    public static function login($user_values = [])
    {
        $factory = new \Minz\Tests\DatabaseFactory('users');
        $user_id = $factory->create($user_values);
        \flusio\utils\CurrentUser::set($user_id);
        return \flusio\utils\CurrentUser::get();
    }

    /**
     * Simulate a user who logs out.
     */
    public static function logout()
    {
        \flusio\utils\CurrentUser::reset();
    }
}
