<?php

namespace App\models;

use App\utils;
use Minz\Request;

class StreamView
{
    public function __construct(
        private readonly Stream $stream,
        public readonly \DateTimeImmutable $at,
        public readonly string $source,
        public readonly string $status,
    ) {
    }

    public static function buildFromRequest(Stream $stream, Request $request): self
    {
        $today = \Minz\Time::now();
        $at = $request->parameters->getDatetime('at', $today, 'Y-m-d');
        $source = $request->parameters->getString('source', '');
        $status = $request->parameters->getString('status', 'all');

        return new self($stream, $at, $source, $status);
    }

    public function isAt(\DateTimeImmutable $at): bool
    {
        return $this->at->format('Y-m-d') === $at->format('Y-m-d');
    }

    public function isSource(Collection $source): bool
    {
        return $this->source === $source->id;
    }

    public function isStatus(string $status): bool
    {
        return $this->status === $status;
    }

    public function urlAt(\DateTimeImmutable $at): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'at' => $at->format('Y-m-d'),
        ]);
    }

    public function urlSource(string $source): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'at' => $this->at->format('Y-m-d'),
            'source' => $source,
        ]);
    }

    public function urlStatus(string $status): string
    {
        return \Minz\Url::for('stream', [
            'id' => $this->stream->id,
            'at' => $this->at->format('Y-m-d'),
            'source' => $this->source,
            'status' => $status,
        ]);
    }

    /**
     * @return \DateTimeImmutable[]
     */
    public function timeline(): array
    {
        $day = \Minz\Time::relative('today midnight');
        $limit = \Minz\Time::relative('-30 days midnight');

        $timeline = [];

        while ($day > $limit) {
            $timeline[] = $day;
            $day = $day->modify('-1 day');
        }

        return $timeline;
    }

    public function linksTimeline(User $context_user): utils\LinksTimeline
    {
        $links = $this->stream->links([
            'context_user' => $context_user,
            'at' => $this->at,
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
                'at' => $this->at,
                'source_id' => $source->id,
            ]);

            if ($count_all === 0) {
                continue;
            }

            $count_unread = $this->stream->countLinks([
                'context_user' => $context_user,
                'at' => $this->at,
                'status' => 'unread',
                'source_id' => $source->id,
            ]);

            $sources_and_counts[] = [$source, $count_all, $count_unread];
        }

        return $sources_and_counts;
    }

    public function countByDay(\DateTimeImmutable $day, User $context_user): int
    {
        return $this->stream->countLinks([
            'context_user' => $context_user,
            'at' => $day,
        ]);
    }
}
