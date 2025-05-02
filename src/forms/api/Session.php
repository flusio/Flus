<?php

namespace App\forms\api;

use App\auth;
use App\models;
use Minz\Form;
use Minz\Request;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Session extends Form
{
    #[Form\Field(transform: '\Minz\Email::sanitize')]
    #[Validable\Presence(message: 'The address email is required.')]
    #[Validable\Email(message: 'The address email is invalid.')]
    public string $email = '';

    #[Form\Field]
    #[Validable\Presence(message: 'The password is required.')]
    public string $password = '';

    #[Form\Field]
    #[Validable\Presence(message: 'The app name is required.')]
    public string $app_name = '';

    private Request $request;

    #[Validable\Check]
    public function checkCredentials(): void
    {
        if ($this->isInvalid('email') || $this->isInvalid('password')) {
            // Don't check credentials if email or password are in error.
            return;
        }

        try {
            $user = $this->user();
        } catch (\RuntimeException $e) {
            $this->addError('@base', 'invalid_credentials', 'The credentials are invalid.');
            return;
        }

        if (
            $user->isSupportUser() ||
            !$user->verifyPassword($this->password)
        ) {
            $this->addError('@base', 'invalid_credentials', 'The credentials are invalid.');
        }
    }

    #[Form\OnHandleRequest]
    public function setRequest(Request $request): void
    {
        $this->request = $request;
    }

    public function session(): models\Session
    {
        return auth\CurrentUser::createApiSession(
            $this->user(),
            $this->app_name,
            $this->request,
        );
    }

    private function user(): models\User
    {
        $user = models\User::findBy(['email' => $this->email]);

        if (!$user) {
            throw new \RuntimeException("Unknown user {$this->email}");
        }

        return $user;
    }
}
