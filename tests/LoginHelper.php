<?php

namespace tests;

use App\auth;
use App\models;
use tests\factories\SessionFactory;
use tests\factories\TokenFactory;
use tests\factories\UserFactory;

/**
 * Provide login utility methods during tests.
 *
 * @phpstan-import-type ModelValues from \Minz\Database\Recordable
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait LoginHelper
{
    /**
     * Simulate a user who logs in. A User is created using a DatabaseFactory.
     *
     * @param ModelValues $user_values
     */
    public function login(array $user_values = [], ?\DateTimeImmutable $confirmed_password_at = null): models\User
    {
        $user = UserFactory::create($user_values);

        $session = auth\CurrentUser::createBrowserSession($user);

        if ($confirmed_password_at) {
            $session->confirmed_password_at = $confirmed_password_at;
            $session->save();
        }

        return $user;
    }

    /**
     * Simulate a user who logs out. It is called before each test to make sure
     * to reset the context.
     */
    #[\PHPUnit\Framework\Attributes\Before]
    public function logout(): void
    {
        auth\CurrentUser::deleteSession();
    }
}
