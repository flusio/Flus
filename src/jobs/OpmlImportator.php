<?php

namespace App\jobs;

use App\models;
use App\services;

/**
 * Job that import feeds from an OPML file
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OpmlImportator extends \Minz\Job
{
    public function __construct()
    {
        parent::__construct();
        $this->queue = 'importators';
    }

    public function perform(int $importation_id): void
    {
        $importation = models\Importation::find($importation_id);
        if (!$importation) {
            \Minz\Log::warning("Importation #{$importation_id} no longer exists, skipping it");
            return;
        }

        $opml_filepath = $importation->options['opml_filepath'];

        if (!is_string($opml_filepath)) {
            throw new \Exception("Importation #{$importation_id} OPML filepath is invalid");
        }

        $user = $importation->user();

        try {
            $opml_importator_service = new services\OpmlImportator($opml_filepath);
            $opml_importator_service->importForUser($user);
            $importation->finish();
        } catch (services\OpmlImportatorError $e) {
            $importation->fail($e->getMessage());
        }

        $importation->save();

        @unlink($opml_filepath);
    }
}
