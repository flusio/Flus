<?php

namespace App\models;

use App\search_engine;
use App\utils;
use Minz\ParameterBag;
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

    public readonly View $view;

    public readonly \DateTimeImmutable $at;

    public readonly int $days;

    public readonly ?Collection $source;

    public readonly string $status;

    /**
     * Whether the links that the user dismissed are displayed. They are hidden
     * by default.
     */
    public readonly bool $with_dismissed;

    /**
     * The search as typed by the user, kept to display it back in the filters.
     */
    public readonly string $query;

    /**
     * The parsed version of the query, or null if the query is empty or
     * cannot be parsed.
     */
    public readonly ?search_engine\Query $search_query;

    /**
     * The date at which the view is rendered. It is passed to the "mark as
     * read" forms so that the links added in background after the rendering
     * are not marked.
     */
    public readonly \DateTimeImmutable $rendered_at;

    public static function buildFromRequest(Stream $stream, ?User $context_user, Request $request): self
    {
        $view = View::loadFromRequest($request, parameter: 'view');

        if (!$view || $view->stream_id !== $stream->id) {
            $view = $stream->defaultView(['context_user' => $context_user]);
        }

        $view->setStream($stream);
        $view->loadUrlParameters($request->parameters);

        return new StreamView($stream, $context_user, $view);
    }

    public function __construct(
        Stream $stream,
        ?User $context_user,
        View $view,
    ) {
        // The view carries normalized parameters (cf. View::loadUrlParameters()):
        // they are interpreted here, not checked again.
        $url_parameters = new ParameterBag($view->current_url_parameters);
        $defaults = $view->defaultUrlParameters();

        $this->stream = $stream;
        $this->context_user = $context_user;
        $this->view = $view;
        $default_at = new \DateTimeImmutable($defaults['at']);
        $this->at = $url_parameters->getDatetime('at', $default_at, 'Y-m-d');
        $this->days = $url_parameters->getInteger('days', (int) $defaults['days']);
        $this->status = $url_parameters->getString('status', $defaults['status']);
        $this->with_dismissed = $url_parameters->getBoolean('with_dismissed', $defaults['with_dismissed'] !== '');
        $this->query = $url_parameters->getString('q', $defaults['q']);
        $this->search_query = search_engine\Query::fromStringOrNull($this->query);
        $this->rendered_at = \Minz\Time::now();

        // The source is set last as isSourceCounted() requires the other
        // properties. It is more restricted here as we don't display sources
        // without any link, but we want to keep it in the view as it may be
        // used to save the filters.
        $source_id = $url_parameters->getString('source', '');
        $source = $source_id ? Collection::find($source_id) : null;

        if ($source && !$this->isSourceCounted($source)) {
            $source = null;
        }

        $this->source = $source;
    }

    public function isViewSelected(View $view): bool
    {
        return $this->view->id === $view->id;
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
        return $this->memoize('period', function (): array {
            $day = \Minz\Time::relative('today midnight');
            $period_days = View::STREAM_PERIOD_DAYS;
            $limit = \Minz\Time::relative("-{$period_days} days midnight");

            $period = [];

            while ($day > $limit) {
                $period[] = $day;
                $day = $day->modify('-1 day');
            }

            return $period;
        });
    }

    public function linksTimeline(): utils\LinksTimeline
    {
        $links = $this->stream->links([
            'context_user' => $this->context_user,
            'at' => $this->at,
            'days' => $this->days,
            'source' => $this->source,
            'status' => $this->status,
            'with_dismissed' => $this->with_dismissed,
            'query' => $this->search_query,
        ]);

        links\Preloader::for($links)
            ->sources()
            ->urlStatusesFor($this->context_user)
            ->numberCollectionsFor($this->context_user);

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
                'with_dismissed' => $this->with_dismissed,
                'query' => $this->search_query,
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
