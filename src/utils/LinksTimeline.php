<?php

namespace App\utils;

use App\models;

/**
 * A class to organise a list of links in a timeline (i.e. grouped by dates).
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinksTimeline
{
    /** @var array<string, LinksTimeline\DateGroup> */
    private array $dates_groups = [];

    /**
     * @param models\Link[] $links
     */
    public function __construct(array $links)
    {
        foreach ($links as $link) {
            if (!$link->published_at) {
                continue;
            }

            $date_key = $link->published_at->format('Y-m-d');
            if (isset($this->dates_groups[$date_key])) {
                $date_group = $this->dates_groups[$date_key];
            } else {
                $date_group = new LinksTimeline\DateGroup($link->published_at);
                $this->dates_groups[$date_key] = $date_group;
            }

            $date_group->addLink($link);
        }
    }

    /**
     * @return array<string, LinksTimeline\DateGroup>
     */
    public function datesGroups(): array
    {
        return $this->dates_groups;
    }

    public function empty(): bool
    {
        return empty($this->dates_groups);
    }

    public function count(): int
    {
        $count = 0;

        foreach ($this->dates_groups as $date_group) {
            $count += $date_group->count();
        }

        return $count;
    }
}
