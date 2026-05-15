<?php

namespace App\utils\LinksTimeline;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OriginGroup
{
    public function __construct(
        public string $origin,
        public string $label,
        /** @var models\Link[] */
        public array $links = [],
    ) {
    }

    public function count(): int
    {
        return count($this->links);
    }
}
