<?php

namespace App\models;

use App\utils;
use Minz\Request;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class StreamView
{
    use utils\Memoizer;

    public function __construct(
        public readonly Stream $stream,
        public readonly \DateTimeImmutable $at,
        public readonly int $days = 1,
        public readonly ?Collection $source = null,
        public readonly string $status = 'all',
    ) {
    }

    public static function buildFromRequest(Stream $stream, Request $request): self
    {
        $today = \Minz\Time::now();
        $at = $request->parameters->getDatetime('at', $today, 'Y-m-d');
        $days = $request->parameters->getInteger('days', 1);
        $status = $request->parameters->getString('status', 'all');
        $source = Collection::loadFromRequest($request, parameter: 'source');

        return new self($stream, $at, $days, $source, $status);
    }

    public function isAt(\DateTimeImmutable $at): bool
    {
        return $this->at->format('Y-m-d') === $at->format('Y-m-d');
    }

    public function isSourceSelected(Collection $source): bool
    {
        return $this->source?->id === $source->id;
    }

    public function isStatusSelected(string $status): bool
    {
        return $this->status === $status;
    }

    public function urlSource(Collection $source): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'at' => $this->at->format('Y-m-d'),
            'days' => $this->days,
            'source' => $source->id,
        ]);
    }

    public function urlStatus(string $status): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'at' => $this->at->format('Y-m-d'),
            'days' => $this->days,
            'source' => $this->source?->id,
            'status' => $status,
        ]);
    }

    /**
     * @return \DateTimeImmutable[]
     */
    public function period(): array
    {
        $day = \Minz\Time::relative('today midnight');
        $limit = \Minz\Time::relative('-30 days midnight');

        $period = [];

        while ($day > $limit) {
            $period[] = $day;
            $day = $day->modify('-1 day');
        }

        return $period;
    }

    public function linksTimeline(?User $context_user = null): utils\LinksTimeline
    {
        $links = $this->stream->links([
            'context_user' => $context_user,
            'at' => $this->at,
            'days' => $this->days,
            'source' => $this->source,
            'status' => $this->status,
        ]);

        return new utils\LinksTimeline($links);
    }

    /**
     * @return list<array{Collection, int, int}>
     */
    public function countedSources(?User $context_user): array
    {
        if ($context_user) {
            $key = "counted_sources_{$context_user->id}";
        } else {
            $key = 'counted_sources';
        }

        return $this->memoize($key, function () use ($context_user): array {
            $sources = $this->stream->sources();

            $sources_and_counts = [];

            foreach ($sources as $source) {
                $count_all = $this->stream->countLinks([
                    'context_user' => $context_user,
                    'at' => $this->at,
                    'days' => $this->days,
                    'source' => $source,
                ]);

                if ($count_all === 0) {
                    continue;
                }

                if ($context_user) {
                    $count_unread = $this->stream->countLinks([
                        'context_user' => $context_user,
                        'at' => $this->at,
                        'days' => $this->days,
                        'source' => $source,
                        'status' => 'unread',
                    ]);
                } else {
                    $count_unread = 0;
                }

                $sources_and_counts[] = [$source, $count_all, $count_unread];
            }

            usort($sources_and_counts, function ($source_and_count_1, $source_and_count_2): int {
                // Sort on the number of total links so the first sources are
                // the ones with the fewest links.
                return $source_and_count_1[1] <=> $source_and_count_2[1];
            });

            return $sources_and_counts;
        });
    }

    public function countByDay(\DateTimeImmutable $day, ?User $context_user = null): int
    {
        return $this->stream->countLinks([
            'context_user' => $context_user,
            'at' => $day,
        ]);
    }
}
