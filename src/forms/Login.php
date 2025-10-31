<?php

namespace App\forms;

use App\models;
use Minz\Form;
use Minz\Request;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Login extends BaseForm
{
    #[Form\Field(transform: '\Minz\Email::sanitize')]
    #[Validable\Email(message: new Translatable('The address email is invalid.'))]
    public string $email = '';

    #[Form\Field]
    public string $password = '';

    #[Form\Field]
    public string $redirect_to = '';

    /**
     * @param array<string, mixed> $default_values
     */
    public function __construct(array $default_values = [])
    {
        if (\App\Configuration::$application['demo']) {
            $default_values['email'] = 'demo@flus.io';
            $default_values['password'] = 'demo';
        }

        parent::__construct($default_values);
    }

    #[Form\OnHandleRequest]
    public function forceRedirectableRedirectTo(Request $request): void
    {
        $router = \Minz\Engine::router();
        if (!$router->isRedirectable($this->redirect_to)) {
            $this->redirect_to = \Minz\Url::for('home');
        }
    }

    #[Validable\Check]
    public function checkCredentials(): void
    {
        if ($this->isInvalid('email')) {
            return;
        }

        try {
            $user = $this->user();
        } catch (\RuntimeException $e) {
            $this->addError(
                'email',
                'invalid_credentials',
                _('We can’t find any account with this email address.')
            );
            return;
        }

        if (!$user->verifyPassword($this->password)) {
            $this->addError(
                'password',
                'invalid_credentials',
                _('The password is incorrect.')
            );
            return;
        }

        if ($user->isSupportUser()) {
            $this->addError(
                '@base',
                'invalid_user',
                _('What are you trying to do? You can’t login to the support account.')
            );
            return;
        }
    }

    public function user(): models\User
    {
        $user = models\User::findBy(['email' => $this->email]);

        if (!$user) {
            throw new \RuntimeException("Unknown user {$this->email}");
        }

        return $user;
    }
}
