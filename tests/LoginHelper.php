<?php

namespace tests;

/**
 * Provide login utility methods during tests.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait LoginHelper
{
    /**
     * Simulate a user who logs in. A User is created using a DatabaseFactory.
     *
     * @param array $user_values Values of the User to create (optional)
     *
     * @return \flusio\models\User
     */
    public function login($user_values = [])
    {
        $factory = new \Minz\Tests\DatabaseFactory('user');
        $user_id = $factory->create($user_values);
        \flusio\utils\CurrentUser::set($user_id);
        return \flusio\utils\CurrentUser::get();
    }

    /**
     * Simulate a user who logs out. It is called before each test to make sure
     * to reset the context.
     *
     * @before
     */
    public function logout()
    {
        \flusio\utils\CurrentUser::reset();
    }
}
