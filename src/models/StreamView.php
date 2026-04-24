<?php

namespace App\models;

use App\utils;
use Minz\Request;

class StreamView
{
    public function __construct(
        private readonly Stream $stream,
        public readonly string $period,
        public readonly string $source,
        public readonly string $status,
    ) {
    }

    public static function buildFromRequest(Stream $stream, Request $request): self
    {
        $period = $request->parameters->getString('period', 'today');
        $source = $request->parameters->getString('source', '');
        $status = $request->parameters->getString('status', 'unread');

        return new self($stream, $period, $source, $status);
    }

    public function after(): \DateTimeImmutable
    {
        if ($this->period === 'last-month') {
            return \Minz\Time::ago(1, 'month');
        } elseif ($this->period === 'last-week') {
            return \Minz\Time::ago(1, 'week');
        } else {
            return \Minz\Time::relative('today');
        }
    }

    public function isPeriod(string $period): bool
    {
        return $this->period === $period;
    }

    public function isSource(Collection $source): bool
    {
        return $this->source === $source->id;
    }

    public function isStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function urlPeriod(string $period): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'period' => $period,
        ]);
    }

    public function urlSource(string $source): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'period' => $this->period,
            'source' => $source,
        ]);
    }

    public function urlStatus(string $status): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'period' => $this->period,
            'source' => $this->source,
            'status' => $status,
        ]);
    }

    public function linksTimeline(User $context_user): utils\LinksTimeline
    {
        $links = $this->stream->links([
            'context_user' => $context_user,
            'after' => $this->after(),
            'status' => $this->status,
            'source_id' => $this->source,
        ]);

        return new utils\LinksTimeline($links);
    }

    /**
     * @return list<array{Collection, int, int}>
     */
    public function countedSources(User $context_user): array
    {
        $sources = $this->stream->sources();

        $sources_and_counts = [];

        foreach ($sources as $source) {
            $count_all = $this->stream->countLinks([
                'context_user' => $context_user,
                'after' => $this->after(),
                'source_id' => $source->id,
            ]);

            if ($count_all === 0) {
                continue;
            }

            $count_unread = $this->stream->countLinks([
                'context_user' => $context_user,
                'after' => $this->after(),
                'status' => 'unread',
                'source_id' => $source->id,
            ]);

            $sources_and_counts[] = [$source, $count_all, $count_unread];
        }

        return $sources_and_counts;
    }
}
