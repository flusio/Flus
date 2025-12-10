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

        if ($content) {
            $this->content = $this->buildContent($content);
        } else {
            $this->content = $this->buildDefaultContent();
        }
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

    public function reply(): ?MastodonStatus
    {
        return $this->memoize('reply', function (): ?MastodonStatus {
            return self::findBy([
                'reply_to_id' => $this->id,
            ]);
        });
    }

    public function isPosted(): bool
    {
        return $this->posted_at !== null;
    }

    /**
     * Return the default content value, built from link information.
     */
    private function buildDefaultContent(): string
    {
        $link = $this->link();
        $account = $this->account();
        $options = $account->options;

        $content = $link->title;
        $content .= "\n\n" . $link->url;

        if (!$link->is_hidden) {
            $url_to_notes = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
            if ($options['link_to_notes']) {
                $content .= "\n" . $url_to_notes;
            }
        }

        if ($options['post_scriptum']) {
            $content .= "\n\n" . $options['post_scriptum'];
        }

        return $content;
    }

    public function buildContent(string $base_content): string
    {
        $account = $this->account();
        $options = $account->options;

        $content = $base_content;
        if ($options['post_scriptum'] && $options['post_scriptum_in_all_posts']) {
            $content .= "\n\n" . $options['post_scriptum'];
        }

        return $content;
    }
}
