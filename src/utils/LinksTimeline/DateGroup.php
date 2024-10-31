<?php

namespace App\utils\LinksTimeline;

use App\models;
use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class DateGroup
{
    public \DateTimeImmutable $date;

    /** @var models\Link[] */
    public array $links = [];

    /** @var array<string, SourceGroup> */
    public array $source_groups = [];

    public function __construct(\DateTimeImmutable $date)
    {
        $this->date = $date;
    }

    public function addLink(models\Link $link): void
    {
        if ($link->group_by_source) {
            $source_key = $link->source_type . '#' . $link->source_resource_id;
            if (isset($this->source_groups[$source_key])) {
                $source_group = $this->source_groups[$source_key];
            } else {
                $source = $link->source();

                if (!$source) {
                    $this->links[] = $link;
                    return;
                }

                $source_group = new SourceGroup($source);
                $this->source_groups[$source_key] = $source_group;
            }

            $source_group->links[] = $link;
        } else {
            $this->links[] = $link;
        }
    }

    public function isToday(): bool
    {
        $today = \Minz\Time::now();
        return $this->date->format('Y-m-d') === $today->format('Y-m-d');
    }

    public function isYesterday(): bool
    {
        $yesterday = \Minz\Time::ago(1, 'day');
        return $this->date->format('Y-m-d') === $yesterday->format('Y-m-d');
    }

    /**
     * Return the source groups sorted by titles.
     *
     * @return SourceGroup[]
     */
    public function sourceGroups(): array
    {
        return utils\Sorter::localeSort($this->source_groups, 'title');
    }

    public function count(): int
    {
        $count = count($this->links);

        foreach ($this->source_groups as $source_group) {
            $count += $source_group->count();
        }

        return $count;
    }
}
