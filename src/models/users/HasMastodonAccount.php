<?php

namespace App\models\users;

use App\models\MastodonAccount;

/**
 * Add methods to access the Mastodon account of the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasMastodonAccount
{
    /**
     * Return whether the user has configured a Mastodon account.
     */
    public function isMastodonEnabled(): bool
    {
        return $this->memoize('is_mastodon_enabled', function (): bool {
            $mastodon_account = MastodonAccount::findBy([
                'user_id' => $this->id,
            ]);

            return $mastodon_account && $mastodon_account->isSetup();
        });
    }

    /**
     * Return the Mastodon account of the user.
     *
     * @throws \Minz\Errors\MissingRecordError
     *     If the user has no account.
     */
    public function mastodonAccount(): MastodonAccount
    {
        return $this->memoize('mastodon_account', function (): MastodonAccount {
            return MastodonAccount::requireBy(['user_id' => $this->id]);
        });
    }
}
