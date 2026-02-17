<?php

namespace App\twig;

use App\utils;
use Twig\Attribute\AsTwigFunction;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NotificationsExtension
{
    /**
     * @return string[]
     */
    #[AsTwigFunction('notifications')]
    public static function notifications(): array
    {
        return utils\Notification::popAll();
    }
}
