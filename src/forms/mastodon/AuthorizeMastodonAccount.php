<?php

namespace App\forms\mastodon;

use App\forms\BaseForm;
use App\models;
use App\services;
use App\utils;
use Minz\Form;
use Minz\Validable;
use Minz\Translatable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AuthorizeMastodonAccount extends BaseForm
{
    use utils\Memoizer;

    #[Form\Field]
    #[Validable\Presence(
        message: new Translatable('The authorization code is missing.'),
    )]
    public string $code = '';

    public function accessToken(): string
    {
        return $this->memoize('access_token', function (): string {
            $mastodon_service = $this->mastodonService();
            return $mastodon_service->accessToken($this->code);
        });
    }

    public function username(): string
    {
        return $this->memoize('username', function (): string {
            $mastodon_account = $this->optionAs('mastodon_account', models\MastodonAccount::class);
            $mastodon_service = $this->mastodonService();
            return $mastodon_service->getUsername($mastodon_account);
        });
    }

    public function mastodonService(): services\Mastodon
    {
        $mastodon_account = $this->optionAs('mastodon_account', models\MastodonAccount::class);
        $mastodon_server = $mastodon_account->server();
        return new services\Mastodon($mastodon_server);
    }
}
