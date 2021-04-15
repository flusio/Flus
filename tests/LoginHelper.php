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
     * @param array $token_values Values of the associated Token (optional)
     * @param array $session_values Values of the associated Session (optional)
     *
     * @return \flusio\models\User
     */
    public function login($user_values = [], $token_values = [], $session_values = [])
    {
        $user_factory = new \Minz\Tests\DatabaseFactory('user');
        $token_factory = new \Minz\Tests\DatabaseFactory('token');
        $session_factory = new \Minz\Tests\DatabaseFactory('session');

        $expired_at = \Minz\Time::fromNow(30, 'days');
        $token_values = array_merge([
            'expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
        ], $token_values);

        $token = $token_factory->create($token_values);
        $user_id = $user_factory->create($user_values);

        $session_values['token'] = $token;
        $session_values['user_id'] = $user_id;
        $session_factory->create($session_values);

        \flusio\auth\CurrentUser::setSessionToken($token);
        return \flusio\auth\CurrentUser::get();
    }

    /**
     * Simulate a user who logs out. It is called before each test to make sure
     * to reset the context.
     *
     * @before
     */
    public function logout()
    {
        \flusio\auth\CurrentUser::reset();
    }
}
