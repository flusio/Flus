<?php

namespace App\models;

use App\search_engine;
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
    public bool $display_unread_in_sidenav = true;

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
        $memoize_key = $this->memoizeKeyFor('sources', $context_user);

        return $this->memoize($memoize_key, function () use ($context_user): array {
            $collections = Collection::listByStream($this, [
                'context_user' => $context_user,
            ]);
            return utils\Sorter::localeSort($collections, 'name');
        });
    }

    /**
     * Return the default view of the stream, or a new unsaved one with the
     * default parameters.
     *
     * The context user is the one who would own the view once saved.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     */
    public function defaultView(array $options = []): View
    {
        $context_user = $options['context_user'] ?? null;
        $memoize_key = $this->memoizeKeyFor('default_view', $context_user);

        return $this->memoize($memoize_key, function () use ($context_user): View {
            return View::findOrBuildDefaultForStream($this, $context_user);
        });
    }

    /**
     * Return the views of the stream, the default one first, then sorted by
     * name.
     *
     * @param array{
     *     context_user?: ?User,
     * } $options
     *
     * @return View[]
     */
    public function views(array $options = []): array
    {
        $context_user = $options['context_user'] ?? null;
        $memoize_key = $this->memoizeKeyFor('views', $context_user);

        return $this->memoize($memoize_key, function () use ($options): array {
            $views = View::listByStream($this);

            // listByStream() only returns the saved views: the default one has
            // to be added by hand as long as it may have not been saved yet.
            array_unshift($views, $this->defaultView($options));

            return $views;
        });
    }

    /**
     * Return a memoize key discriminating on the context user, e.g.
     * "sources_<user id>", or "sources_anonymous" if no user is given.
     */
    private function memoizeKeyFor(string $prefix, ?User $context_user): string
    {
        $suffix = $context_user ? $context_user->id : 'anonymous';
        return "{$prefix}_{$suffix}";
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
     * Add the given sources to the stream.
     *
     * @param Collection[] $sources
     */
    public function addSources(array $sources): void
    {
        $database = Database::get();
        $database->beginTransaction();

        foreach ($sources as $source) {
            $this->addSource($source);
        }

        $database->commit();
    }

    /**
     * Remove the given sources from the stream.
     *
     * @param Collection[] $sources
     */
    public function removeSources(array $sources): void
    {
        $database = Database::get();
        $database->beginTransaction();

        foreach ($sources as $source) {
            $this->removeSource($source);
        }

        $database->commit();
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
     *     with_dismissed?: bool,
     *     query?: ?search_engine\Query,
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
     *     with_dismissed?: bool,
     *     query?: ?search_engine\Query,
     * } $options
     *
     * @return array<string, int>
     */
    public function countLinksPerSource(array $options = []): array
    {
        return Link::countByStreamPerSource($this, $options);
    }

    /**
     * Return whether the unread links must be indicated in the sidenav.
     *
     * The has_unread_links property must have been computed (see listByUser()).
     */
    public function displaysUnreadInSidenav(): bool
    {
        return $this->display_unread_in_sidenav && $this->has_unread_links === true;
    }

    /**
     * List the streams in which the followed collection is a source.
     *
     * @return self[]
     */
    public static function listByFollow(FollowedCollection $follow): array
    {
        $sql = <<<SQL
            SELECT s.*
            FROM streams s, streams_to_follows sf

            WHERE s.id = sf.stream_id
            AND sf.follow_id = :follow_id
        SQL;

        $database = \Minz\Database::get();
        $statement = $database->prepare($sql);
        $statement->execute([
            'follow_id' => $follow->id,
        ]);

        return self::fromDatabaseRows($statement->fetchAll());
    }

    /**
     * List the streams owned by the given user.
     *
     * The has_unread_links property is always set: it indicates whether the
     * stream contains unread links published during the last seven days.
     * It is only computed for the streams displaying the unread links in the
     * sidenav, and set to false for the others.
     *
     * @return self[]
     */
    public static function listByUser(User $user): array
    {
        // List all the streams
        $streams = self::listBy(['user_id' => $user->id]);

        // Then, the rest of this method is dedicated to calculating the
        // "unread" information for each stream.
        // This method is executed on every page of the application: it is
        // deliberately made of flat queries rather than of a subquery correlated
        // on the streams, which would redo a whole join over seven days for each
        // of them.
        // It also allows to regroup business logic (i.e. sources visibility,
        // unread status) in the classes it belongs instead of duplicating it.

        // Filter the sources for which we need to calculate the unread information.
        $streams_with_unread_dot = array_filter($streams, function (self $stream): bool {
            return $stream->display_unread_in_sidenav;
        });

        // Get the sources of the "unread-filtered" streams.
        $sources_by_stream_ids = Collection::listByStreams($streams_with_unread_dot, [
            'context_user' => $user,
        ]);

        $sources = array_values($sources_by_stream_ids);
        $sources = array_merge(...$sources);
        // Deduplicate the sources by id as a same source can be in several streams.
        $sources = array_column($sources, null, 'id');
        $sources = array_values($sources);

        // Filter the sources that contain unread links.
        $unread_sources = Link::filterSourcesWithUnreadLinks($user, $sources, [
            'at' => \Minz\Time::now(),
            'days' => 7,
        ]);
        $unread_source_ids = array_column($unread_sources, 'id');

        // Finally, set the has_unread_links attribute manually based on what we
        // calculated.
        foreach ($streams as $stream) {
            $stream_sources = $sources_by_stream_ids[$stream->id] ?? [];
            $stream_source_ids = array_column($stream_sources, 'id');

            $stream->has_unread_links = array_intersect($stream_source_ids, $unread_source_ids) !== [];
        }

        return $streams;
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
