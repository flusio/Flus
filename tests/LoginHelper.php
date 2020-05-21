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
        $user_factory = new \Minz\Tests\DatabaseFactory('user');
        $token_factory = new \Minz\Tests\DatabaseFactory('token');
        $session_factory = new \Minz\Tests\DatabaseFactory('session');

        $expired_at = \Minz\Time::fromNow(30, 'days');
        $token = $token_factory->create([
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);
        $user_id = $user_factory->create($user_values);
        $session_factory->create([
            'user_id' => $user_id,
            'token' => $token,
        ]);

        \flusio\utils\CurrentUser::setSessionToken($token);
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
