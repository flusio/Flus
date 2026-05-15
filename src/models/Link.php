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
    use Database\Resource;
    use Fetchable;
    use Validable;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

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
    #[Validable\Comparison(
        greater_or_equal: 0,
        message: new Translatable('The reading time must be greater or equal to 0.'),
    )]
    public int $reading_time = 0;

    #[Database\Column]
    public ?string $image_filename = null;

    #[Database\Column]
    public string $origin = '';

    #[Database\Column]
    public string $user_id;

    /** @var 'unset'|'ok' */
    #[Database\Column]
    public string $user_fetched_status = 'unset';

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
    public ?string $initial_collection_id = null;

    #[Database\Column(computed: true)]
    public ?\DateTimeImmutable $published_at = null;

    #[Database\Column(computed: true)]
    public ?int $number_notes = null;

    #[Database\Column(computed: true)]
    public string $search_index;

    #[Database\Column(computed: true)]
    public string $url_hash;

    public function __construct(string $url, string $user_id, bool $is_hidden = false)
    {
        $url = \SpiderBits\Url::sanitize($url);

        $this->id = \Minz\Random::timebased();
        $this->title = $url;
        $this->url = $url;
        $this->url_hash = self::hashUrl($url);
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
        $link_copied->fetched_retry_at = $link->fetched_retry_at;
        $link_copied->setOrigin('');

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
     * Set the link's collections.
     *
     * @param Collection[] $collections
     */
    public function setCollections(
        array $collections,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        $collection_ids = array_column($collections, 'id');
        LinkToCollection::setCollections($this->id, $collection_ids, $at);

        if ($sync_publication_frequency) {
            foreach ($collections as $collection) {
                $collection->syncPublicationFrequencyPerYear();
                $collection->save();
            }
        }
    }

    /**
     * Add the link to the collections.
     *
     * @param Collection[] $collections
     */
    public function addCollections(
        array $collections,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        $collection_ids = array_column($collections, 'id');
        LinkToCollection::attach([$this->id], $collection_ids, $at);

        if ($sync_publication_frequency) {
            foreach ($collections as $collection) {
                $collection->syncPublicationFrequencyPerYear();
                $collection->save();
            }
        }
    }

    /**
     * Add the link to a collection.
     */
    public function addCollection(
        Collection $collection,
        ?\DateTimeImmutable $at = null,
        bool $sync_publication_frequency = true,
    ): void {
        $this->addCollections([$collection], $at, $sync_publication_frequency);
    }

    /**
     * Remove the link from the collections.
     *
     * @param Collection[] $collections
     */
    public function removeCollections(
        array $collections,
        bool $sync_publication_frequency = true,
    ): void {
        $collection_ids = array_column($collections, 'id');
        LinkToCollection::detach([$this->id], $collection_ids);

        if ($sync_publication_frequency) {
            foreach ($collections as $collection) {
                $collection->syncPublicationFrequencyPerYear();
                $collection->save();
            }
        }
    }

    /**
     * Remove the link from a collection.
     */
    public function removeCollection(
        Collection $collection,
        bool $sync_publication_frequency = true,
    ): void {
        $this->removeCollections([$collection], $sync_publication_frequency);
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

    /**
     * Return a new note.
     *
     * It is initialized with this link and the link's user. The note is not
     * saved in database yet.
     */
    public function initNote(): Note
    {
        return new Note($this->user_id, $this->id);
    }

    public function numberNotes(): int
    {
        if ($this->number_notes !== null) {
            return $this->number_notes;
        } else {
            return Note::countBy([
                'link_id' => $this->id,
            ]);
        }
    }

    /**
     * Set the origin of the link.
     *
     * It is useful to keep the old source_type and source_resource_id columns
     * in sync even if they are not used anymore. This is to ease an eventual
     * rollback if the new system doesn't work or isn't efficient enough.
     */
    public function setOrigin(string $origin): void
    {
        $this->origin = $origin;

        $this->source_type = '';
        $this->source_resource_id = null;

        if ($origin) {
            list($origin_type, $origin_id) = utils\OriginHelper::extractFromPath($origin);

            if ($origin_type) {
                $this->source_type = $origin_type;
                $this->source_resource_id = $origin_id;
            }
        }
    }

    public function origin(): ?Origin
    {
        if (!$this->origin) {
            return null;
        }

        return new Origin($this->origin);
    }

    /**
     * Return the (deprecated) source.
     *
     * @deprecated
     */
    public function source(): ?string
    {
        $origin = $this->origin();

        if (!$origin || !$origin->model) {
            return null;
        }

        $source_type = match ($origin->model::class) {
            User::class => 'user',
            Collection::class => 'collection',
            default => '',
        };

        if (!$source_type) {
            return null;
        }

        return "{$source_type}#{$origin->model->id}";
    }

    /**
     * Return whether or not the given user has the link URL in its news.
     */
    public function isInNewsOf(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $news = $user->news();
        return Link::isUrlInCollectionId($news->id, $this->url);
    }

    /**
     * Return whether or not the given user has the link URL in its bookmarks.
     */
    public function isInBookmarksOf(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $bookmarks = $user->bookmarks();
        return Link::isUrlInCollectionId($bookmarks->id, $this->url);
    }

    /**
     * Return whether or not the given user read the link URL.
     */
    public function isReadBy(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $read_list = $user->readList();
        return Link::isUrlInCollectionId($read_list->id, $this->url);
    }

    /**
     * Return whether or not the the link URL is in the given collection.
     */
    public function isInNeverList(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $never_list = $user->neverList();
        return Link::isUrlInCollectionId($never_list->id, $this->url);
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
     * Mark the link as accessible to the user.
     */
    public function markAsAccessibleToUser(): void
    {
        $this->user_fetched_status = 'ok';
    }

    /**
     * Reset information that the link is accessible to the user.
     */
    public function resetIsAccessibleToUser(): void
    {
        $this->user_fetched_status = 'unset';
    }

    /**
     * Return whether the link is inaccessible or not.
     *
     * It returns false if the link is inaccessible to the server, but that
     * the user indicated it is accessible to them.
     */
    public function isInaccessible(): bool
    {
        return $this->isInaccessibleToServer() && !$this->isAccessibleToUser();
    }

    /**
     * Return whether the link is inaccessible or not to the server.
     */
    public function isInaccessibleToServer(): bool
    {
        $is_fetched = $this->fetched_at !== null;
        $is_error_code = $this->fetched_code < 200 || $this->fetched_code >= 400;
        return $is_fetched && $is_error_code;
    }

    /**
     * Return whether the link is explicitely marked as accessible to the user.
     */
    public function isAccessibleToUser(): bool
    {
        return $this->user_fetched_status === 'ok';
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

    /**
     * @return array<string, mixed>
     */
    public function toJson(User $context_user): array
    {
        $origin_model = $this->origin();
        $source = null;

        if ($context_user->id === $this->user_id && $origin_model) {
            $source = $this->source();
        }

        return [
            'id' => $this->id,
            'created_at' => $this->created_at->format(\DateTime::ATOM),
            'title' => $this->title,
            'url' => $this->url,
            'is_hidden' => $this->is_hidden,
            'reading_time' => $this->reading_time,
            'tags' => $this->tags,
            'source' => $source, // @deprecated Can be removed in version 3.0.0.
            'is_read' => $this->isReadBy($context_user),
            'is_read_later' => $this->isInBookmarksOf($context_user),
            'collections' => array_column($this->collections(), 'id'),
            'published_at' => $this->published_at?->format(\DateTime::ATOM),
            'number_notes' => $this->numberNotes(),
        ];
    }
}
