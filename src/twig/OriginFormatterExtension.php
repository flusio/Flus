<?php

namespace App\twig;

use App\auth;
use App\models;
use App\utils;
use Twig\Attribute\AsTwigFilter;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OriginFormatterExtension
{
    #[AsTwigFilter('format_origin_url')]
    public static function formatOriginUrl(string $origin): string
    {
        return self::getFormatter()->urlFromOrigin($origin);
    }

    #[AsTwigFilter('format_origin_label')]
    public static function formatOriginLabel(string $origin): string
    {
        return self::getFormatter()->labelFromOrigin($origin);
    }

    #[AsTwigFilter('origin_owner')]
    public static function originOwner(string $origin): ?models\User
    {
        return self::getFormatter()->ownerFromOrigin($origin);
    }

    private static function getFormatter(): utils\OriginFormatter
    {
        return utils\OriginFormatter::instance(auth\CurrentUser::get());
    }
}
