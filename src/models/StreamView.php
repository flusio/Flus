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
    public function __construct(
        public readonly Stream $stream,
        public readonly \DateTimeImmutable $at,
        public readonly int $days = 1,
    ) {
    }

    public static function buildFromRequest(Stream $stream, Request $request): self
    {
        $today = \Minz\Time::now();
        $at = $request->parameters->getDatetime('at', $today, 'Y-m-d');
        $days = $request->parameters->getInteger('days', 1);

        return new self($stream, $at, $days);
    }

    public function isAt(\DateTimeImmutable $at): bool
    {
        return $this->at->format('Y-m-d') === $at->format('Y-m-d');
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
        ]);

        return new utils\LinksTimeline($links);
    }

    public function countByDay(\DateTimeImmutable $day, ?User $context_user = null): int
    {
        return $this->stream->countLinks([
            'context_user' => $context_user,
            'at' => $day,
        ]);
    }
}
