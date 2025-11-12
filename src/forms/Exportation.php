<?php

namespace App\forms;

use App\models;
use App\utils;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Exportation extends BaseForm
{
    use utils\Memoizer;

    public function exportation(): models\Exportation
    {
        $user = $this->user();
        $current_exportation = $this->currentExportation();

        if ($current_exportation && $current_exportation->isOngoing()) {
            throw new \LogicException("User {$user->id} has an ongoing exportation.");
        } elseif ($current_exportation) {
            @unlink($current_exportation->filepath);
            $current_exportation->remove();
            $this->unmemoize('current_exportation');
        }

        return new models\Exportation($user->id);
    }

    public function currentExportation(): ?models\Exportation
    {
        return $this->memoize('current_exportation', function (): ?models\Exportation {
            $user = $this->user();
            return models\Exportation::findByUser($user);
        });
    }

    #[Validable\Check]
    public function checkNoOngoingExportation(): void
    {
        $current_exportation = $this->currentExportation();
        if ($current_exportation && $current_exportation->isOngoing()) {
            $this->addError(
                '@base',
                'ongoing_exportation',
                _('You already have an ongoing exportation.'),
            );
            return;
        }
    }

    public function user(): models\User
    {
        $user = $this->options->get('user');

        if (!($user instanceof models\User)) {
            throw new \LogicException('User must be passed as an option of the form.');
        }

        return $user;
    }
}
