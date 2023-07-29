<?php

namespace flusio\models;

use flusio\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * Represent a list containing a set of links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'collections')]
class Collection
{
    use dao\Collection;
    use Database\Recordable;
    use Validable;

    public const VALID_TYPES = ['bookmarks', 'news', 'read', 'never', 'collection', 'feed'];

    public const NAME_MAX_LENGTH = 100;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public ?\DateTimeImmutable $locked_at = null;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The name is required.'),
    )]
    #[Validable\Length(
        max: self::NAME_MAX_LENGTH,
        message: new Translatable('The name must be less than {max} characters.'),
    )]
    public string $name = '';

    #[Database\Column]
    public string $description = '';

    /** @var value-of<self::VALID_TYPES> */
    #[Database\Column]
    public string $type = 'collection';

    #[Database\Column]
    public bool $is_public = false;

    #[Database\Column]
    public ?string $image_filename = null;

    #[Database\Column]
    public ?\DateTimeImmutable $image_fetched_at = null;

    #[Database\Column]
    public string $user_id;

    #[Database\Column]
    public ?string $group_id = null;

    #[Database\Column]
    public ?string $feed_url = null;

    #[Database\Column]
    public ?string $feed_type = null;

    #[Database\Column]
    public ?string $feed_site_url = null;

    #[Database\Column]
    public ?string $feed_last_hash = null;

    #[Database\Column]
    public int $feed_fetched_code = 0;

    #[Database\Column]
    public ?\DateTimeImmutable $feed_fetched_at = null;

    #[Database\Column]
    public ?string $feed_fetched_error = null;

    #[Database\Column(computed: true)]
    public ?int $number_links = null;

    #[Database\Column(computed: true)]
    public ?string $time_filter = null;

    public function __construct()
    {
        $this->id = \Minz\Random::timebased();
    }

    public static function init(
        string $user_id,
        string $name,
        string $description,
        bool $is_public,
    ): self {
        $collection = new self();

        $collection->name = trim($name);
        $collection->description = trim($description);
        $collection->is_public = $is_public;
        $collection->user_id = $user_id;

        return $collection;
    }

    public static function initBookmarks(string $user_id): self
    {
        $collection = new self();

        $collection->name = _('Bookmarks');
        $collection->type = 'bookmarks';
        $collection->user_id = $user_id;

        return $collection;
    }

    public static function initReadList(string $user_id): self
    {
        $collection = new self();

        $collection->name = _('Links read');
        $collection->type = 'read';
        $collection->user_id = $user_id;

        return $collection;
    }

    public static function initNeverList(string $user_id): self
    {
        $collection = new self();

        $collection->name = _('Links never to read');
        $collection->type = 'never';
        $collection->user_id = $user_id;

        return $collection;
    }

    public static function initNews(string $user_id): self
    {
        $collection = new self();

        $collection->name = _('News');
        $collection->type = 'news';
        $collection->user_id = $user_id;

        return $collection;
    }

    public static function initFeed(string $user_id, string $feed_url): self
    {
        $collection = new self();

        $collection->name = utils\Belt::cut($feed_url, self::NAME_MAX_LENGTH);
        $collection->feed_url = \SpiderBits\Url::sanitize($feed_url);
        $collection->type = 'feed';
        $collection->user_id = $user_id;
        $collection->is_public = true;

        return $collection;
    }

    /**
     * Return the name of the collection.
     *
     * If the collection is one of bookmarks, read or news types, the localized
     * version is returned.
     */
    public function name(): string
    {
        if ($this->type === 'bookmarks') {
            return _('Bookmarks');
        } elseif ($this->type === 'read') {
            return _('Links read');
        } elseif ($this->type === 'news') {
            return _('News');
        } else {
            return $this->name;
        }
    }

    /**
     * Return the owner of the collection.
     */
    public function owner(): User
    {
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("Collection #{$this->id} has invalid user.");
        }

        return $user;
    }

    /**
     * Return links of the current collection.
     *
     * @see Link::listComputedByCollectionId
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'hidden'?: bool,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * @return Link[]
     */
    public function links(array $selected_computed_props = [], array $options = []): array
    {
        return Link::listComputedByCollectionId(
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return a link from this collection with the given URL and not owned by
     * the given user.
     */
    public function linkNotOwnedByUrl(string $user_id, string $url_lookup): ?Link
    {
        return Link::findNotOwnedByCollectionIdAndUrl(
            $user_id,
            $this->id,
            $url_lookup,
        );
    }

    /**
     * Return the group if any for a given user.
     *
     * If the collection is owned by the user, the group is the one directly
     * attached to the current collection. Otherwise, the group is the one
     * attached to a corresponding FollowedCollection.
     */
    public function groupForUser(string $user_id): ?Group
    {
        if ($this->user_id === $user_id && $this->group_id) {
            return Group::find($this->group_id);
        } else {
            $followed_collection = FollowedCollection::findBy([
                'user_id' => $user_id,
                'collection_id' => $this->id,
            ]);
            if ($followed_collection && $followed_collection->group_id) {
                return Group::find($followed_collection->group_id);
            }
        }

        return null;
    }

    /**
     * Return the topics attached to the current collection.
     *
     * @return Topic[]
     */
    public function topics(): array
    {
        return Topic::listByCollectionId($this->id);
    }

    /**
     * Return the CollectionShares attached to the current collection
     *
     * @see CollectionShare::listComputedByCollectionId
     *
     * @param array{
     *     'access_type'?: 'any'|'read'|'write',
     * } $options
     *
     * @return CollectionShare[]
     */
    public function shares(array $options = []): array
    {
        $collection_shares = CollectionShare::listComputedByCollectionId(
            $this->id,
            ['username'],
            $options,
        );
        return utils\Sorter::localeSort($collection_shares, 'username');
    }

    /**
     * Return whether the collections is shared with the given user.
     *
     * If $access_type is 'any' or 'read', the method returns true just if a
     * collection_share exists for this collection and user.
     *
     * If $access_type is 'write', the method will check that the collection
     * share has a 'write' type.
     */
    public function sharedWith(User $user, string $access_type = 'any'): bool
    {
        $existing_collection_share = CollectionShare::findBy([
            'collection_id' => $this->id,
            'user_id' => $user->id,
        ]);

        if (!$existing_collection_share) {
            return false;
        }

        return (
            $access_type === 'any' ||
            $access_type === 'read' ||
            $access_type === $existing_collection_share->type
        );
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \Minz\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:collections/{$this->id}";
    }

    /**
     * Return the feed site URL to be displayed
     */
    public function feedWebsite(): string
    {
        if ($this->feed_site_url) {
            return utils\Belt::host($this->feed_site_url);
        } elseif ($this->feed_url) {
            return utils\Belt::host($this->feed_url);
        } else {
            return '';
        }
    }

    /**
     * Return the description as HTML (from Markdown).
     */
    public function descriptionAsHtml(): string
    {
        if ($this->type === 'collection') {
            $markdown = new utils\MiniMarkdown();
            return $markdown->text($this->description);
        } else {
            if ($this->feed_site_url) {
                $site_url = $this->feed_site_url;
            } elseif ($this->feed_url) {
                $site_url = $this->feed_url;
            } else {
                $site_url = '';
            }

            return utils\HtmlSanitizer::sanitizeCollectionDescription($this->description, $site_url);
        }
    }
}
