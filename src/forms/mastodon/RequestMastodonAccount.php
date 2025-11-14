<?php

namespace App\forms\mastodon;

use App\forms\BaseForm;
use App\models;
use App\services;
use App\utils;
use Minz\Form;
use Minz\Request;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class RequestMastodonAccount extends BaseForm
{
    use utils\Memoizer;

    #[Form\Field(transform: '\\SpiderBits\\Url::sanitize')]
    #[Validable\Url(
        message: new Translatable('The URL is invalid.'),
    )]
    public string $host = '';

    public function mastodonService(): services\Mastodon
    {
        return $this->memoize('mastodon_service', function (): services\Mastodon {
            return services\Mastodon::get($this->host);
        });
    }

    public function mastodonAccount(): models\MastodonAccount
    {
        $mastodon_service = $this->mastodonService();
        $user = $this->optionAs('user', models\User::class);

        return models\MastodonAccount::findOrCreate(
            $mastodon_service->server,
            $user,
        );
    }

    #[Validable\Check]
    public function checkUserDoesNotHaveExistingAccount(): void
    {
        $user = $this->optionAs('user', models\User::class);
        $mastodon_account = models\MastodonAccount::findByUser($user);

        if ($mastodon_account && $mastodon_account->isSetup()) {
            $this->addError(
                '@base',
                'existing_account',
                _('You already have configured a Mastodon account.'),
            );
        }
    }
}
