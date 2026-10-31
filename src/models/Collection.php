<?php

namespace App\models;

use App\utils;
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
    use collections\Discoverable;
    use collections\Fetchable;
    use collections\Followable;
    use collections\Groupable;
    use collections\HasLinks;
    use collections\InStreams;
    use collections\OwnedByUser;
    use collections\Prunable;
    use collections\Publishing;
    use collections\Shareable;
    use collections\Statistics;
    use dao\BulkQueries;
    use dao\MediaQueries;
    use Database\Lockable;
    use Database\Recordable;
    use Database\Resource;
    use utils\Memoizer;
    use Validable;

    public const VALID_TYPES = ['journal', 'collection', 'feed'];

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

    public static function initCollection(string $user_id): self
    {
        $collection = new self();

        $collection->type = 'collection';
        $collection->user_id = $user_id;

        return $collection;
    }

    public function isFeed(): bool
    {
        return $this->type === 'feed';
    }

    public function isCollection(): bool
    {
        return $this->type === 'collection';
    }

    /**
     * Return the name of the collection.
     *
     * If the collection is the journal, the localized version is returned.
     */
    public function name(): string
    {
        if ($this->type === 'journal') {
            return _('Journal');
        } else {
            return $this->name;
        }
    }

    /**
     * Return the URL of the feed to subscribe to the collection.
     *
     * For feeds, it corresponds to the URL of the original feed. For the other
     * collections, it corresponds to the "collection feed" route. In this
     * case, $direct indicates if the links of the generated feed should point
     * directly to the external websites.
     */
    public function feedUrl(bool $direct = false): string
    {
        if ($this->isFeed() && $this->feed_url) {
            return $this->feed_url;
        }

        $parameters = ['id' => $this->id];

        if ($direct) {
            $parameters['direct'] = 'true';
        }

        return \Minz\Url::absoluteFor('collection feed', $parameters);
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
        return "tag:{$host},{$date}:collections/{$this->id}";
    }

    /**
     * Return the description as HTML (from Markdown).
     */
    public function descriptionAsHtml(): string
    {
        if ($this->type === 'collection') {
            $markdown = new utils\MiniMarkdown(context_user: $this->owner());
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

    /**
     * @return array<string, mixed>
     */
    public function toJson(User $context_user): array
    {
        if ($this->type === 'feed') {
            return [
                'id' => $this->id,
                'name' => $this->nameByUser($context_user),
                'description' => $this->description,
                'group' => null,
                'url' => $this->feed_url,
                'type' => $this->feed_type,
                'site_url' => $this->feed_site_url,
                'is_followed' => $context_user->isFollowing($this),
                'publication_frequency_per_year' => $this->publication_frequency_per_year,
            ];
        } else {
            $group = $context_user->id === $this->user_id ? $this->group() : null;

            return [
                'id' => $this->id,
                'name' => $this->nameByUser($context_user),
                'description' => $this->description,
                'group' => $group?->name,
                'is_public' => $this->is_public,
                'is_followed' => $context_user->isFollowing($this),
                'publication_frequency_per_year' => $this->publication_frequency_per_year,
            ];
        }
    }

    /**
     * Return the clause limiting the collections (aliased as "c") to those
     * that the given user can view, or to the public ones if no user is given.
     *
     * The :user_id parameter must be bound when a user is given.
     *
     * @see \App\auth\CollectionsAccess::canView
     */
    private static function buildVisibilityClause(?User $user): string
    {
        if (!$user) {
            return 'AND c.is_public = true';
        }

        return <<<SQL
            AND (
                c.is_public = true
                OR c.user_id = :user_id
                OR EXISTS (
                    SELECT 1 FROM collection_shares cs
                    WHERE cs.user_id = :user_id
                    AND cs.collection_id = c.id
                )
            )
        SQL;
    }
}
