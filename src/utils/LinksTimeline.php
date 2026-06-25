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

            // The key is formatted using translateDate (using IntlDateFormatter
            // internally) because the output can differ from the format method
            // of DateTimeInterface. This happens because of the timezone. For
            // instance, a "2026-06-24 22:30:00" DateTime in UTC+2 would be
            // formatted "2026-06-24" with DateTime::format, and "2026-06-25"
            // with TwigExtension::translateDate. As we display the dates in
            // the interface using the latter, the key MUST be coherent.
            $date_key = \Minz\Template\TwigExtension::translateDate($link->published_at, 'Y-MM-dd');

            if (isset($this->dates_groups[$date_key])) {
                $date_group = $this->dates_groups[$date_key];
            } else {
                $date = new \DateTimeImmutable($date_key);
                $date_group = new LinksTimeline\DateGroup($date);
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
