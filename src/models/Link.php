<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'links')]
class Link
{
    use dao\Link;
    use Database\Recordable;
    use Validable;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public ?\DateTimeImmutable $locked_at = null;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The title is required.'),
    )]
    public string $title;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The link is required.'),
    )]
    #[Validable\Url(
        message: new Translatable('The link is invalid.'),
    )]
    public string $url;

    /** @var string[] */
    #[Database\Column]
    public array $url_feeds = [];

    #[Database\Column]
    public string $url_replies = '';

    #[Database\Column]
    public bool $is_hidden = true;

    #[Database\Column]
    public int $reading_time = 0;

    #[Database\Column]
    public ?string $image_filename = null;

    #[Database\Column]
    public bool $to_be_fetched = true;

    #[Database\Column]
    public ?\DateTimeImmutable $fetched_at = null;

    #[Database\Column]
    public int $fetched_code = 0;

    #[Database\Column]
    public ?string $fetched_error = null;

    #[Database\Column]
    public int $fetched_count = 0;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public ?string $feed_entry_id = null;

    #[Database\Column]
    public string $source_type = '';

    #[Database\Column]
    public ?string $source_resource_id = null;

    #[Database\Column]
    public bool $group_by_source = false;

    /** @var string[] */
    #[Database\Column]
    public array $tags = [];

    #[Database\Column(computed: true)]
    public ?string $source_news_type = null;

    #[Database\Column(computed: true)]
    public ?string $source_news_resource_id = null;

    #[Database\Column(computed: true)]
    public ?\DateTimeImmutable $published_at = null;

    #[Database\Column(computed: true)]
    public ?int $number_notes = null;

    #[Database\Column(computed: true)]
    public string $search_index;

    #[Database\Column(computed: true)]
    public string $url_hash;

    public function __construct(string $url, string $user_id, bool $is_hidden)
    {
        $url = \SpiderBits\Url::sanitize($url);

        $this->id = \Minz\Random::timebased();
        $this->title = $url;
        $this->url = $url;
        $this->is_hidden = $is_hidden;
        $this->user_id = $user_id;
    }

    public function refreshTags(): void
    {
        $tags = [];

        foreach ($this->notes() as $note) {
            $tags = array_merge($tags, $note->tags());
        }

        $this->setTags($tags);
        $this->save();
    }

    /**
     * @param string[] $tags
     */
    public function setTags(array $tags): void
    {
        $sanitized_tags = [];

        foreach ($tags as $tag) {
            $lower_tag = mb_strtolower($tag);

            if (!isset($sanitized_tags[$lower_tag])) {
                $sanitized_tags[$lower_tag] = $tag;
            }
        }

        $this->tags = $sanitized_tags;
    }

    /**
     * Copy a Link to the given user.
     */
    public static function copy(self $link, string $user_id): self
    {
        $link_copied = new self($link->url, $user_id, false);

        $link_copied->title = $link->title;
        $link_copied->url_feeds = $link->url_feeds;
        $link_copied->url_replies = $link->url_replies;
        $link_copied->image_filename = $link->image_filename;
        $link_copied->reading_time = $link->reading_time;
        $link_copied->fetched_at = $link->fetched_at;
        $link_copied->fetched_code = $link->fetched_code;
        $link_copied->fetched_count = $link->fetched_count;
        $link_copied->source_type = '';

        return $link_copied;
    }

    /**
     * Return the owner of the link.
     */
    public function owner(): User
    {
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("Link #{$this->id} has invalid user.");
        }

        return $user;
    }

    /**
     * Return the collections attached to the current link
     *
     * @return Collection[]
     */
    public function collections(): array
    {
        return Collection::listByLinkId($this->id);
    }

    /**
     * Return the notes attached to the current link
     *
     * @return Note[]
     */
    public function notes(): array
    {
        return Note::listByLink($this);
    }

    /**
     * Return the notepad, containing the notes grouped by dates
     *
     * @return array<string, Note[]>
     */
    public function notepad(): array
    {
        $notepad = [];

        foreach ($this->notes() as $note) {
            $date_iso = $note->created_at->format('Y-m-d');
            $notepad[$date_iso][] = $note;
        }

        return $notepad;
    }

    public function sourceCollection(): ?Collection
    {
        if (
            $this->source_type !== 'collection' ||
            !$this->source_resource_id
        ) {
            return null;
        }

        return Collection::find($this->source_resource_id);
    }

    public function sourceUser(): ?User
    {
        if (
            $this->source_type !== 'user' ||
            !$this->source_resource_id
        ) {
            return null;
        }

        return User::find($this->source_resource_id);
    }

    public function source(): User|Collection|null
    {
        if ($this->source_type == 'user') {
            return $this->sourceUser();
        } elseif ($this->source_type == 'collection') {
            return $this->sourceCollection();
        } else {
            return null;
        }
    }

    /**
     * Return whether or not the given user has the link URL in its bookmarks.
     */
    public function isInBookmarksOf(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return self::isUrlInBookmarksOfUserId($user->id, $this->url);
    }

    /**
     * Return whether or not the given user read the link URL.
     */
    public function isReadBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return Link::isUrlReadByUserId($user->id, $this->url);
    }

    /**
     * Return whether the link is shared with the given user or not (i.e. it is
     * attached to a shared collection or to an owned collection).
     *
     * If $access_type is 'any' or 'read', the method returns true just if a
     * collection_share exists for this collection and user.
     *
     * If $access_type is 'write', the method will check that the collection
     * share has a 'write' type.
     *
     * $access_type has no effect if a link is in an owned collection (i.e. it
     * implies the user has write effect over it).
     */
    public function sharedWith(User $user, string $access_type = 'any'): bool
    {
        return (
            Collection::existsForUserIdAndLinkId($user->id, $this->id) ||
            CollectionShare::existsForUserIdAndLinkId($user->id, $this->id, $access_type)
        );
    }

    /**
     * Return whether the link URL is a feed URL.
     */
    public function isFeedUrl(): bool
    {
        return in_array($this->url, $this->url_feeds);
    }

    public function host(): string
    {
        return utils\Belt::host($this->url);
    }

    /**
     * Return whether a link is in error or not.
     */
    public function inError(): bool
    {
        return $this->fetched_at !== null && (
            $this->fetched_code < 200 || $this->fetched_code >= 400
        );
    }

    /**
     * Return whether trackers are detected in the URL.
     */
    public function trackersDetected(): bool
    {
        $cleared_url = \SpiderBits\ClearUrls::clear($this->url);
        return $this->url !== $cleared_url;
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \App\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:links/{$this->id}";
    }

    public static function hashUrl(string $url): string
    {
        return hash('sha256', $url);
    }
}
