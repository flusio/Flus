<?php

namespace App\jobs;

use App\models;
use App\services;

/**
 * Job to export data.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Exportator extends \Minz\Job
{
    public function perform(int $exportation_id): void
    {
        $exportation = models\Exportation::find($exportation_id);

        if (!$exportation) {
            throw new \Exception("Can’t find exportation {$exportation_id}");
        }

        if ($exportation->status !== 'ongoing') {
            return;
        }

        $exportations_path = \App\Configuration::$data_path . '/exportations';
        if (!file_exists($exportations_path)) {
            @mkdir($exportations_path);
        }

        try {
            $exportation_service = new services\DataExporter($exportations_path);
            $filepath = $exportation_service->export($exportation->user_id);
            $exportation->finish($filepath);
        } catch (\Exception $e) {
            $exportation->fail($e->getMessage());
        }

        $exportation->save();
    }
}
