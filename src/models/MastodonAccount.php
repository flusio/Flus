<?php

namespace App\models;

use Minz\Database;

/**
 * @phpstan-type Options array{
 *     'link_to_comment': 'always'|'never'|'auto',
 *     'post_scriptum': string,
 * }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'mastodon_accounts')]
class MastodonAccount
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public int $mastodon_server_id;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public string $access_token = '';

    #[Database\Column]
    public string $username;

    /** @var Options */
    #[Database\Column]
    public array $options;

    public function __construct(MastodonServer $mastodon_server, User $user)
    {
        $this->mastodon_server_id = $mastodon_server->id;
        $this->user_id = $user->id;
        $this->username = '';
        $this->options = [
            'link_to_comment' => 'auto',
            'post_scriptum' => '',
        ];
    }

    public static function findOrCreate(MastodonServer $mastodon_server, User $user): self
    {
        $account = self::findBy([
            'mastodon_server_id' => $mastodon_server->id,
            'user_id' => $user->id,
        ]);

        if (!$account) {
            $account = new self($mastodon_server, $user);
            $account->save();
        }

        return $account;
    }

    public static function findByUser(User $user): ?self
    {
        return self::findBy(['user_id' => $user->id]);
    }

    /**
     * Return the user associated to the account.
     */
    public function user(): User
    {
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("MastodonAccount #{$this->id} has invalid user.");
        }

        return $user;
    }

    /**
     * Return the MastodonServer associated to the account.
     */
    public function server(): MastodonServer
    {
        $server = MastodonServer::find($this->mastodon_server_id);

        if (!$server) {
            throw new \Exception("MastodonAccount #{$this->id} has invalid server.");
        }

        return $server;
    }

    public function isSetup(): bool
    {
        return $this->access_token !== '';
    }

    /**
     * Return a MastodonStatus initialized for this account.
     */
    public function buildMastodonStatus(Link $link, ?Note $note): MastodonStatus
    {
        return new MastodonStatus($this, $link, $note);
    }
}
