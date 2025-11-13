<?php

namespace App\forms;

use App\auth;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait DemoDisabler
{
    #[Validable\Check]
    public function checkNotDemoAccount(): void
    {
        $user = auth\CurrentUser::get();
        if (\App\Configuration::isDemoEnabled() && $user && $user->isDemoAccount()) {
            $this->addError(
                '@base',
                'demo_account',
                _('Sorry but you cannot do that in the demo 😉'),
            );
        }
    }
}
