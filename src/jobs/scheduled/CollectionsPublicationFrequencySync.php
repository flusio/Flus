<?php

namespace App\jobs\scheduled;

use App\models;

/**
 * Job to synchronize the publication_frequency_per_year attribute of the
 * collections. This must be done daily as the frequency depends on the current
 * date.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionsPublicationFrequencySync extends \Minz\Job
{
    public static function install(): void
    {
        $job = new self();

        if (!\Minz\Job::existsBy(['name' => $job->name])) {
            $perform_at = \Minz\Time::relative('tomorrow 3:00');
            $job->performLater($perform_at);
        }
    }

    public function __construct()
    {
        parent::__construct();
        $this->frequency = '+1 day';
    }

    public function perform(): void
    {
        $collections = models\Collection::listToSyncPublicationFrequency();

        foreach ($collections as $collection) {
            $collection->syncPublicationFrequencyPerYear();
            $collection->save();
        }
    }
}
