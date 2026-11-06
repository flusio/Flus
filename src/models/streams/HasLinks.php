<?php

namespace App\models\streams;

use App\models\Link;
use App\models\Source;
use App\models\User;
use App\search_engine;

/**
 * Add the relation between a stream and the links of its sources.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasLinks
{
    /**
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int|'ALL',
     *     sources?: Source[],
     *     status?: string,
     *     with_dismissed?: bool,
     *     query?: ?search_engine\Query,
     *     created_before?: ?\DateTimeImmutable,
     *     limit?: int|'ALL',
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
     */
    public function displaysUnreadInSidenav(User $user): bool
    {
        return $this->display_unread_in_sidenav && $this->hasUnreadLinks($user);
    }

    /**
     * Return whether the stream contains unread links published during the
     * last seven days.
     */
    public function hasUnreadLinks(User $user): bool
    {
        return $this->memoize(['has_unread_links', $user], function () use ($user): bool {
            $streams_with_unread = self::listWithUnreadLinks([$this], $user);
            return isset($streams_with_unread[$this->id]);
        });
    }

    /**
     * Set whether the stream contains unread links for the given user without
     * querying the database.
     *
     * @see Preloader
     */
    public function preloadHasUnreadLinks(User $user, bool $has_unread_links): void
    {
        $this->memoizeValue(['has_unread_links', $user], $has_unread_links);
    }

    /**
     * Return the given streams that contain unread links published during the
     * last seven days, indexed by their ids.
     *
     * @param self[] $streams
     *
     * @return array<string, self>
     */
    public static function listWithUnreadLinks(array $streams, User $context_user): array
    {
        // This method is executed on every page of the application (through
        // the sidenav): it is deliberately made of flat queries rather than of
        // a subquery correlated on the streams, which would redo a whole join
        // over seven days for each of them.
        // It also allows to regroup business logic (i.e. sources visibility,
        // unread status) in the classes it belongs instead of duplicating it.

        // Get the sources of the streams.
        $sources_by_stream_ids = Source::listByStreams($streams, [
            'context_user' => $context_user,
        ]);

        $sources = array_values($sources_by_stream_ids);
        $sources = array_merge(...$sources);
        // Deduplicate the sources by id as a same source can be in several streams.
        $sources = array_column($sources, null, 'id');
        $sources = array_values($sources);

        // Filter the sources that contain unread links.
        $unread_sources = Link::filterSourcesWithUnreadLinks($context_user, $sources, [
            'at' => \Minz\Time::now(),
            'days' => 7,
        ]);
        $unread_source_ids = array_column($unread_sources, 'id');

        // Finally, keep the streams of which at least one source has unread links.
        $streams_with_unread = [];

        foreach ($streams as $stream) {
            $stream_sources = $sources_by_stream_ids[$stream->id] ?? [];
            $stream_source_ids = array_column($stream_sources, 'id');

            if (array_intersect($stream_source_ids, $unread_source_ids) !== []) {
                $streams_with_unread[$stream->id] = $stream;
            }
        }

        return $streams_with_unread;
    }
}
