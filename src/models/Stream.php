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
#[Database\Table(name: 'streams')]
class Stream
{
    use Database\Recordable;
    use Database\Resource;
    use Validable;
    use dao\MediaQueries;
    use utils\Memoizer;

    public const NAME_MAX_LENGTH = 100;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

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

    #[Database\Column]
    public bool $is_public = false;

    #[Database\Column]
    public ?string $image_filename = null;

    #[Database\Column]
    public string $user_id;

    #[Database\Column(computed: true)]
    public ?bool $has_unread_links = null;

    public function __construct(User $user)
    {
        $this->id = \Minz\Random::timebased();
        $this->setOwner($user);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * Return the description as HTML (from Markdown).
     */
    public function descriptionAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown(context_user: $this->owner());
        return $markdown->text($this->description());
    }

    public function url(): string
    {
        return \Minz\Url::absoluteFor('stream', ['id' => $this->id]);
    }

    public function owner(): User
    {
        return $this->memoize('owner', function (): User {
            return User::require($this->user_id);
        });
    }

    public function setOwner(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('owner', $user);
    }

    /**
     * Return the sources of the stream.
     *
     * Only the sources that the context user can view are returned, or only
     * the public ones if no context user is given.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     *
     * @return Collection[]
     */
    public function sources(array $options = []): array
    {
        $context_user = $options['context_user'] ?? null;

        if ($context_user) {
            $memoize_key = "sources_{$context_user->id}";
        } else {
            $memoize_key = 'sources_anonymous';
        }

        return $this->memoize($memoize_key, function () use ($context_user): array {
            $collections = Collection::listByStream($this, [
                'context_user' => $context_user,
            ]);
            return utils\Sorter::localeSort($collections, 'name');
        });
    }

    public function hasSource(Collection $source): bool
    {
        return StreamToFollow::find($this, $source) !== null;
    }

    public function addSource(Collection $source): void
    {
        StreamToFollow::findOrCreate($this, $source);
        $this->unmemoizePrefixed('sources_');
    }

    public function removeSource(Collection $source): void
    {
        $stream_to_follow = StreamToFollow::find($this, $source);
        if ($stream_to_follow) {
            $stream_to_follow->remove();
        }
        $this->unmemoizePrefixed('sources_');
    }

    /**
     * Return the sum of the publication frequencies of the sources.
     *
     * Only the sources that the context user can view are taken into account,
     * or only the public ones if no context user is given.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     */
    public function publicationFrequencyPerYear(array $options = []): int
    {
        $sources = $this->sources($options);
        return array_sum(array_column($sources, 'publication_frequency_per_year'));
    }

    /**
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     *     source?: ?Collection,
     *     status?: string,
     *     created_before?: ?\DateTimeImmutable,
     * } $options
     *
     * @return Link[]
     */
    public function links(array $options = []): array
    {
        return Link::listByStream($this, $options);
    }

    /**
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     * } $options
     *
     * @return array<string, array{int, int}>
     */
    public function countLinksPerDay(array $options = []): array
    {
        return Link::countByStreamPerDay($this, $options);
    }

    /**
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     *     status?: string,
     * } $options
     *
     * @return array<string, int>
     */
    public function countLinksPerSource(array $options = []): array
    {
        return Link::countByStreamPerSource($this, $options);
    }

    /**
     * List the streams owned by the given user.
     *
     * The has_unread_links property is always computed: it indicates whether
     * the stream contains unread links published during the past week.
     *
     * The joins and the clauses of the subquery must be kept in sync with the
     * ones of dao\Link::buildStreamJoin() and dao\Link::buildStreamWhere().
     *
     * @return self[]
     */
    public static function listByUser(User $user): array
    {
        $unread_start = \Minz\Time::ago(6, 'days')->modify('00:00:00');
        $unread_end = \Minz\Time::now()->modify('23:59:59');

        $sql = <<<SQL
            SELECT s.*, EXISTS (
                SELECT 1
                FROM streams_to_follows sf

                INNER JOIN followed_collections fc ON sf.follow_id = fc.id
                INNER JOIN links_to_collections lc ON fc.collection_id = lc.collection_id
                INNER JOIN collections c ON fc.collection_id = c.id
                INNER JOIN links l ON lc.link_id = l.id
                LEFT JOIN url_statuses us ON us.user_id = :user_id AND us.url_hash = l.url_hash

                WHERE sf.stream_id = s.id
                AND l.is_hidden = false
                AND lc.created_at >= :unread_start AND lc.created_at <= :unread_end

                AND (
                    us.read_at IS NULL
                    AND us.read_later_at IS NULL
                    AND us.dismissed_at IS NULL
                )

                AND (
                    (l.is_hidden = false AND c.is_public = true)
                    OR c.user_id = :user_id
                    OR EXISTS (
                        SELECT 1 FROM collection_shares cs
                        WHERE cs.user_id = :user_id
                        AND cs.collection_id = c.id
                    )
                )
            ) AS has_unread_links
            FROM streams s

            WHERE s.user_id = :user_id
        SQL;

        $database = Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            ':user_id' => $user->id,
            ':unread_start' => $unread_start->format(Database\Column::DATETIME_FORMAT),
            ':unread_end' => $unread_end->format(Database\Column::DATETIME_FORMAT),
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
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
        return "tag:{$host},{$date}:streams/{$this->id}";
    }
}
