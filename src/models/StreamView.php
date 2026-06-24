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
        public readonly int $days,
    ) {
    }

    public static function buildFromRequest(Stream $stream, Request $request): self
    {
        $today = \Minz\Time::now();
        $at = $request->parameters->getDatetime('at', $today, 'Y-m-d');
        $days = $request->parameters->getInteger('days', 1);

        return new self($stream, $at, $days);
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
}
