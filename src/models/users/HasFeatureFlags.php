<?php

namespace App\models\users;

use App\models\FeatureFlag;

/**
 * Add methods to manage the feature flags enabled for the users.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait HasFeatureFlags
{
    public function enableBeta(): void
    {
        FeatureFlag::enable('beta', $this);
        $this->unmemoize('feature_flag_beta');
    }

    public function disableBeta(): void
    {
        FeatureFlag::disable('beta', $this);
        $this->unmemoize('feature_flag_beta');
    }

    public function isBetaEnabled(): bool
    {
        return $this->memoize('feature_flag_beta', function (): bool {
            return FeatureFlag::isEnabled('beta', $this);
        });
    }

    public function isAlphaEnabled(): bool
    {
        return $this->memoize('feature_flag_alpha', function (): bool {
            return FeatureFlag::isEnabled('alpha', $this);
        });
    }
}
