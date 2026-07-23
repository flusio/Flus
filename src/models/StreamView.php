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

    public readonly Stream $stream;

    public readonly ?User $context_user;

    public readonly \DateTimeImmutable $at;

    public readonly int $days;

    public readonly ?Collection $source;

    public readonly string $status;

    /**
     * The date at which the view is rendered. It is passed to the "mark as
     * read" forms so that the links added in background after the rendering
     * are not marked.
     */
    public readonly \DateTimeImmutable $rendered_at;

    public function __construct(
        Stream $stream,
        ?User $context_user,
        \DateTimeImmutable $at,
        int $days = 1,
        ?Collection $source = null,
        string $status = 'all',
    ) {
        $period = $this->period();
        $period_end = $period[0];
        $period_start = $period[count($period) - 1];
        $at = min(max($at, $period_start), $period_end);

        $days = min(max($days, 1), 7);

        if (!in_array($status, ['all', 'unread', 'read', 'read-later'])) {
            $status = 'all';
        }

        $this->stream = $stream;
        $this->context_user = $context_user;
        $this->at = $at;
        $this->days = $days;
        $this->status = $status;
        $this->rendered_at = \Minz\Time::now();

        // The source is checked last as isSourceCounted() requires the other
        // properties to be set. A source can be selected while having no link
        // over the period (e.g. after changing the date). It would then be
        // filtered out of the sources list, with no way to unselect it: better
        // forget about it.
        if ($source && !$this->isSourceCounted($source)) {
            $source = null;
        }

        $this->source = $source;
    }

    public static function buildFromRequest(Stream $stream, ?User $context_user, Request $request): self
    {
        $today = \Minz\Time::now();
        $at = $request->parameters->getDatetime('at', $today, 'Y-m-d');
        $days = $request->parameters->getInteger('days', 1);
        $status = $request->parameters->getString('status', 'all');
        $source = Collection::loadFromRequest($request, parameter: 'source');

        return new self($stream, $context_user, $at, $days, $source, $status);
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

    public function linksTimeline(): utils\LinksTimeline
    {
        $links = $this->stream->links([
            'context_user' => $this->context_user,
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
    public function countedSources(): array
    {
        return $this->memoize('counted_sources', function (): array {
            $counts_per_source = $this->stream->countLinksPerSource([
                'context_user' => $this->context_user,
                'at' => $this->at,
                'days' => $this->days,
            ]);

            $sources = $this->stream->sources();

            $sources_and_counts = [];

            foreach ($sources as $source) {
                // The sources without links over the period are not counted.
                if (!isset($counts_per_source[$source->id])) {
                    continue;
                }

                list($count_all, $count_unread) = $counts_per_source[$source->id];

                $sources_and_counts[] = [$source, $count_all, $count_unread];
            }

            usort($sources_and_counts, function (array $source_and_count_1, array $source_and_count_2): int {
                // Sort on the number of total links so the first sources are the
                // ones with the fewest links. This is intended as users may
                // prefer to treat sources with the fewest links first.
                return $source_and_count_1[1] <=> $source_and_count_2[1];
            });

            return $sources_and_counts;
        });
    }

    private function isSourceCounted(Collection $source): bool
    {
        foreach ($this->countedSources() as $source_and_count) {
            if ($source_and_count[0]->id === $source->id) {
                return true;
            }
        }

        return false;
    }

    public function countByDay(\DateTimeImmutable $day): int
    {
        // The counts of the whole period are loaded at once: countByDay() is
        // called for each day displayed by the filters.
        $counts_per_day = $this->memoize('counts_per_day', function (): array {
            $period = $this->period();

            return $this->stream->countLinksPerDay([
                'context_user' => $this->context_user,
                'at' => $period[0],
                'days' => count($period),
            ]);
        });

        return $counts_per_day[$day->format('Y-m-d')] ?? 0;
    }
}
