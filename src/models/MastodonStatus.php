<?php

namespace App\models;

use App\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'mastodon_statuses')]
class MastodonStatus
{
    use Database\Recordable;
    use utils\Memoizer;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $content = '';

    #[Database\Column]
    public string $status_id = '';

    #[Database\Column]
    public ?\DateTimeImmutable $posted_at = null;

    #[Database\Column]
    public int $mastodon_account_id;

    #[Database\Column]
    public ?string $reply_to_id = null;

    #[Database\Column]
    public string $link_id;

    public function __construct(MastodonAccount $account, Link $link, string $content = '')
    {
        $this->id = \Minz\Random::timebased();
        $this->setAccount($account);
        $this->setLink($link);
        $this->setReplyTo($this->lastLinkPostedStatus());

        if (!$content) {
            $content = $this->buildDefaultContent();
        }

        $this->content = $content;
    }

    public function account(): MastodonAccount
    {
        return $this->memoize('mastodon_account', function (): MastodonAccount {
            return MastodonAccount::require($this->mastodon_account_id);
        });
    }

    public function setAccount(MastodonAccount $account): void
    {
        $this->mastodon_account_id = $account->id;
        $this->memoizeValue('mastodon_account', $account);
    }

    public function link(): Link
    {
        return $this->memoize('link', function (): Link {
            return Link::require($this->link_id);
        });
    }

    public function setLink(Link $link): void
    {
        $this->link_id = $link->id;
        $this->memoizeValue('link', $link);
    }

    public function replyTo(): ?MastodonStatus
    {
        return $this->memoize('reply_to', function (): ?MastodonStatus {
            if (!$this->reply_to_id) {
                return null;
            }

            return self::require($this->reply_to_id);
        });
    }

    public function setReplyTo(?MastodonStatus $status): void
    {
        $this->reply_to_id = $status?->id;
        $this->memoizeValue('reply_to', $status);
    }

    public function isReply(): bool
    {
        return $this->reply_to_id !== null;
    }

    /**
     * Return the default content value, built from link information.
     */
    private function buildDefaultContent(): string
    {
        $link = $this->link();
        $account = $this->account();
        $options = $account->options;

        $content = self::truncateString($link->title, 250);
        $content .= "\n\n" . $link->url;

        $url_to_link = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
        $content .= "\n" . $url_to_link;

        if ($options['post_scriptum']) {
            $content .= "\n\n" . $options['post_scriptum'];
        }

        return $content;
    }

    /**
     * Truncate a string to a maximum of characters by appending "…" at the end
     * of the string.
     */
    private static function truncateString(string $string, int $max_chars): string
    {
        $string_size = mb_strlen($string);

        if ($string_size < $max_chars) {
            return $string;
        }

        return trim(mb_substr($string, 0, $max_chars - 1)) . '…';
    }

    /**
     * Return the last posted status corresponding to the current status' link.
     * This allows to fetch the reply status id.
     */
    public function lastLinkPostedStatus(): ?MastodonStatus
    {
        $sql = <<<SQL
            SELECT * FROM mastodon_statuses ms

            WHERE ms.link_id = :link_id
            AND ms.posted_at IS NOT NULL
            AND ms.status_id != ''

            ORDER BY ms.posted_at DESC
            LIMIT 1
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':link_id' => $this->link_id,
        ]);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
        }
    }
}
