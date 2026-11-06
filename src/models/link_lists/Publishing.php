<?php

namespace App\models\link_lists;

use App\models\Link;
use Minz\Database;

/**
 * Add the information about the rate at which a source publishes links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Publishing
{
    #[Database\Column]
    public int $publication_frequency_per_year = 0;

    /**
     * Calculate and set the publication frequency of the source.
     */
    public function syncPublicationFrequencyPerYear(): void
    {
        $today = \Minz\Time::now();
        $one_year_ago = \Minz\Time::ago(1, 'year');

        $count_links = Link::countByList($this, [
            'since' => $one_year_ago,
        ]);

        if ($count_links === 0) {
            $this->publication_frequency_per_year = 0;
            return;
        }

        $oldest_published_at = Link::getOldestPublicationDateSince($this, $one_year_ago);

        if ($oldest_published_at === null) {
            $this->publication_frequency_per_year = 0;
            return;
        }

        $diff_since_oldest_link = $today->diff($oldest_published_at);
        $number_of_days = intval($diff_since_oldest_link->format('%a')) + 1;

        $this->publication_frequency_per_year = (int) round($count_links / $number_of_days * 365);
    }

    /**
     * Return the time filter suggested to receive the links of this source
     * in the journal.
     *
     * With the "normal" filter, a source publishing more than one link a
     * day would flood the journal, while a source publishing less than one
     * link a week would often bring nothing.
     *
     * Above a few links a day, no filter can keep the journal readable: the
     * source is better followed in a stream, so "none" is suggested.
     *
     * "all" is never suggested as it would be too easy to get overwhelmed
     * by the journal.
     */
    public function suggestedTimeFilter(): string
    {
        if ($this->publication_frequency_per_year >= 3 * 365) {
            // At least three links per day.
            return 'none';
        }

        if ($this->publication_frequency_per_year >= 365) {
            // At least one link per day.
            return 'strict';
        }

        return 'normal';
    }

    /**
     * Return the publication rate of the source as a group, to be displayed
     * or filtered on.
     *
     * The groups are the ranges delimited by the thresholds of
     * suggestedTimeFilter(), which doesn't distinguish a source publishing
     * nothing from a quiet one.
     */
    public function publicationFrequencyGroup(): string
    {
        if ($this->publication_frequency_per_year === 0) {
            return 'inactive';
        }

        $groups = [
            'normal' => 'weekly',
            'strict' => 'daily',
            'none' => 'prolific',
        ];

        return $groups[$this->suggestedTimeFilter()];
    }
}
