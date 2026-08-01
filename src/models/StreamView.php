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
        // to count (e.g. after changing the date or the status). It would then
        // be filtered out of the sources list, with no way to unselect it:
        // better forget about it.
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

    /**
     * Return whether the given day is part of the displayed period, i.e.
     * between "at - (days - 1)" and "at".
     */
    public function isInRange(\DateTimeImmutable $day): bool
    {
        $start = $this->at->modify('-' . ($this->days - 1) . ' days');
        $start = $start->format('Y-m-d');
        $end = $this->at->format('Y-m-d');

        $day = $day->format('Y-m-d');

        return $start <= $day && $day <= $end;
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
     * @return list<array{Collection, int}>
     */
    public function countedSources(): array
    {
        return $this->memoize('counted_sources', function (): array {
            $counts_per_source = $this->stream->countLinksPerSource([
                'context_user' => $this->context_user,
                'at' => $this->at,
                'days' => $this->days,
                'status' => $this->status,
            ]);

            $sources = $this->stream->sources([
                'context_user' => $this->context_user,
            ]);

            $sources_and_counts = [];

            foreach ($sources as $source) {
                // The sources without links over the period are not counted.
                if (!isset($counts_per_source[$source->id])) {
                    continue;
                }

                $sources_and_counts[] = [$source, $counts_per_source[$source->id]];
            }

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
        $counts = $this->countsPerDay()[$day->format('Y-m-d')] ?? [0, 0];

        return $counts[0];
    }

    public function countUnreadByDay(\DateTimeImmutable $day): int
    {
        $counts = $this->countsPerDay()[$day->format('Y-m-d')] ?? [0, 0];

        return $counts[1];
    }

    /**
     * @return array<string, array{int, int}>
     */
    private function countsPerDay(): array
    {
        // The counts of the whole period are loaded at once: countByDay() and
        // countUnreadByDay() are called for each day displayed by the filters.
        return $this->memoize('counts_per_day', function (): array {
            $period = $this->period();

            return $this->stream->countLinksPerDay([
                'context_user' => $this->context_user,
                'at' => $period[0],
                'days' => count($period),
            ]);
        });
    }

    public function maxCountPerDay(): int
    {
        $max_count = 0;

        foreach ($this->period() as $day) {
            $max_count = max($max_count, $this->countByDay($day));
        }

        return $max_count;
    }
}
