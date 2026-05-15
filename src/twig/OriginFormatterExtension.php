<?php

namespace App\twig;

use App\auth;
use App\utils;
use Twig\Attribute\AsTwigFilter;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OriginFormatterExtension
{
    private static ?utils\OriginFormatter $origin_formatter = null;

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

    private static function getFormatter(): utils\OriginFormatter
    {
        if (!self::$origin_formatter) {
            $context_user = auth\CurrentUser::get();
            self::$origin_formatter = new utils\OriginFormatter($context_user);
        }

        return self::$origin_formatter;
    }
}
