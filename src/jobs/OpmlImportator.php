<?php

namespace flusio\jobs;

use flusio\models;
use flusio\services;

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

    /**
     * @param string $importation_id
     */
    public function perform($importation_id)
    {
        $importation = models\Importation::find($importation_id);
        if (!$importation) {
            \Minz\Log::warning("Importation #{$importation_id} no longer exists, skipping it");
            return;
        }

        $opml_filepath = $importation->options['opml_filepath'];
        $user = models\User::find($importation->user_id);

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
