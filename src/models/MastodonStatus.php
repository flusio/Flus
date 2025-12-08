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

    #[Database\Column]
    public ?string $note_id = null;

    public function __construct(MastodonAccount $account, Link $link, ?Note $note)
    {
        $this->id = \Minz\Random::timebased();
        $this->setAccount($account);
        $this->setLink($link);
        $this->setNote($note);

        $this->content = $this->buildDefaultContent();
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
        $this->memoizer_cache['mastodon_account'] = $account;
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
        $this->memoizer_cache['link'] = $link;
    }

    public function note(): ?Note
    {
        return $this->memoize('note', function (): ?Note {
            if (!$this->note_id) {
                return null;
            }

            return Note::require($this->note_id);
        });
    }

    public function setNote(?Note $note): void
    {
        $this->note_id = $note?->id;
        $this->memoizer_cache['note'] = $note;
    }

    /**
     * Return the default content value, built from link and note information.
     */
    private function buildDefaultContent(): string
    {
        $link = $this->link();
        $note = $this->note();
        $options = $this->account()->options;

        $max_chars = 500;
        $count_chars = 0;

        $content = self::truncateString($link->title, 250);
        $count_chars += mb_strlen($content);

        $content .= "\n\n" . $link->url;
        // Mastodon always considers 23 characters for a URL (also, don’t
        // forget the new line chars).
        $count_chars += 2 + 23;

        if (
            $options['link_to_comment'] === 'always' ||
            ($options['link_to_comment'] === 'auto' && $note)
        ) {
            $url_to_link = \Minz\Url::absoluteFor('link', ['id' => $link->id]);
            $content .= "\n" . $url_to_link;

            if (\App\Configuration::$url_options['host'] === 'localhost') {
                // Mastodon doesn't count localhost links as URLs
                $count_chars += 1 + mb_strlen($url_to_link);
            } else {
                $count_chars += 1 + 23;
            }
        }

        $post_scriptum = '';
        if ($options['post_scriptum']) {
            $post_scriptum = "\n\n" . $options['post_scriptum'];
            $count_chars += 2 + mb_strlen($options['post_scriptum']);
        }

        if ($note) {
            $note_content = self::truncateString($note->content, $max_chars - $count_chars - 2);
            $content = $content . "\n\n" . $note_content;
        }

        $content .= $post_scriptum;

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
}
