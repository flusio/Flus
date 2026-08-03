<?php

namespace App\models;

use App\auth;
use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @phpstan-import-type DatabaseCriteria from Database\Recordable
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'links')]
class Link
{
    use dao\BulkQueries;
    use dao\MediaQueries;
    use Database\Recordable;
    use Database\Resource;
    use Fetchable;
    use links\Annotable;
    use links\InCollections;
    use links\InFollowedCollections;
    use links\InStreams;
    use links\OwnedByUser;
    use links\Prunable;
    use links\Reachable;
    use links\Readable;
    use links\Statistics;
    use links\Taggable;
    use utils\Memoizer;
    use Validable;

    public const ORIGIN_MAX_LENGTH = 2000;

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
    #[Validable\Length(
        max: self::ORIGIN_MAX_LENGTH,
        message: new Translatable('The origin must be less than {max} characters.'),
    )]
    public string $origin = '';

    #[Database\Column]
    public bool $origin_is_public = false;

    #[Database\Column]
    public ?string $feed_entry_id = null;

    #[Database\Column]
    public ?string $source_id = null;

    #[Database\Column]
    public string $source_type = '';

    #[Database\Column]
    public ?string $source_resource_id = null;

    #[Database\Column]
    public bool $group_by_source = false;

    #[Database\Column(computed: true)]
    public ?\DateTimeImmutable $published_at = null;

    #[Database\Column(computed: true)]
    public string $search_index;

    #[Database\Column(computed: true)]
    public string $url_hash;

    public function __construct(string $url, ?User $user = null, bool $is_hidden = false)
    {
        $url = \SpiderBits\Url::sanitize($url);

        $this->id = \Minz\Random::timebased();
        $this->title = $url;
        $this->url = $url;
        $this->url_hash = utils\Belt::hashUrl($url);
        $this->is_hidden = $is_hidden;
        $this->setOwner($user);
    }

    /**
     * Return a link with its computed properties.
     *
     * @param DatabaseCriteria $criteria
     *     The conditions the link must match.
     * @param string[] $selected_computed_props
     *     The list of computed properties to return. It is mandatory to
     *     select specific properties to avoid computing dispensable
     *     properties.
     */
    public static function findComputedBy(array $criteria, array $selected_computed_props): ?self
    {
        // Note that publication date is usually computed by considering the
        // date of association with a collection. Without collection, we
        // consider its date of insertion in the database.
        $published_at_clause = '';
        if (in_array('published_at', $selected_computed_props)) {
            $published_at_clause = ', l.created_at AS published_at';
        }

        $number_notes_clause = '';
        if (in_array('number_notes', $selected_computed_props)) {
            $number_notes_clause = <<<'SQL'
                , (
                    SELECT COUNT(*) FROM notes m
                    WHERE m.link_id = l.id
                ) AS number_notes
            SQL;
        }

        list($where_statement, $parameters) = Database\Helper::buildWhere($criteria);

        $sql = <<<SQL
            SELECT
                l.*
                {$published_at_clause}
                {$number_notes_clause}
            FROM links l
            WHERE {$where_statement}
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute($parameters);

        $result = $statement->fetch();
        if (is_array($result)) {
            return self::fromDatabaseRow($result);
        } else {
            return null;
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

    /**
     * @return array<string, mixed>
     */
    public function toJson(User $context_user): array
    {
        $origin = null;

        if ($this->origin && auth\Access::can($context_user, 'viewOrigin', $this)) {
            $origin_formatter = new utils\OriginFormatter($context_user);

            $origin = [
                'value' => $this->origin,
                'label' => $origin_formatter->labelFromOrigin($this->origin),
                'url' => $origin_formatter->urlFromOrigin($this->origin),
            ];
        }

        return [
            'id' => $this->id,
            'created_at' => $this->created_at->format(\DateTime::ATOM),
            'title' => $this->title,
            'url' => $this->url,
            'is_hidden' => $this->is_hidden,
            'reading_time' => $this->reading_time,
            'tags' => $this->tags,
            'source' => $this->source_id,
            'origin' => $origin,
            'is_read' => $context_user->hasRead($this),
            'is_read_later' => $context_user->hasReadLater($this),
            'collections' => array_column($this->collections(), 'id'),
            'published_at' => $this->published_at?->format(\DateTime::ATOM),
            'number_notes' => $this->numberNotes(),
        ];
    }
}
