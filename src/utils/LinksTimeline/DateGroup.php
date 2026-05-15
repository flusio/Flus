<?php

namespace App\utils\LinksTimeline;

use App\auth;
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

    /** @var array<string, OriginGroup> */
    public array $origin_groups = [];

    private utils\OriginFormatter $origin_formatter;

    public function __construct(\DateTimeImmutable $date)
    {
        $this->date = $date;
        $current_user = auth\CurrentUser::get();
        $this->origin_formatter = new utils\OriginFormatter($current_user);
    }

    public function addLink(models\Link $link): void
    {
        if ($link->origin) {
            if (isset($this->origin_groups[$link->origin])) {
                $origin_group = $this->origin_groups[$link->origin];
            } else {
                $label = $this->origin_formatter->labelFromOrigin($link->origin);
                $origin_group = new OriginGroup($link->origin, $label);
                $this->origin_groups[$link->origin] = $origin_group;
            }

            $origin_group->links[] = $link;
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
     * Return the origin groups sorted by titles.
     *
     * @return OriginGroup[]
     */
    public function originGroups(): array
    {
        return utils\Sorter::localeSort($this->origin_groups, 'label');
    }

    public function count(): int
    {
        $count = count($this->links);

        foreach ($this->origin_groups as $origin_group) {
            $count += $origin_group->count();
        }

        return $count;
    }
}
