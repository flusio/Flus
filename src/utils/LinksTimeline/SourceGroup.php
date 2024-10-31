<?php

namespace App\utils\LinksTimeline;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SourceGroup
{
    /** @var models\Collection|models\User */
    public mixed $source;

    public string $title;

    public string $reference;

    /** @var models\Link[] */
    public array $links = [];

    /**
     * @param models\Collection|models\User $source
     **/
    public function __construct(mixed $source)
    {
        $this->source = $source;

        if ($this->source instanceof models\Collection) {
            $this->title = $this->source->name();
            $this->reference = "collection#{$this->source->id}";
        } else {
            $this->title = $this->source->username;
            $this->reference = "user#{$this->source->id}";
        }
    }

    public function count(): int
    {
        return count($this->links);
    }
}
