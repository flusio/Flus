<?php

namespace App\twig;

use Twig\Attribute\AsTwigFilter;

class LocaleExtension
{
    /**
     * Transform a locale to BCP47 format
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/lang
     * @see https://www.ietf.org/rfc/bcp/bcp47.txt
     */
    #[AsTwigFilter('locale_to_bcp_47')]
    public static function localeToBcp47(string $locale): string
    {
        $splitted_locale = explode('_', $locale, 2);

        if (count($splitted_locale) === 1) {
            return $splitted_locale[0];
        }

        return $splitted_locale[0] . '-' . strtoupper($splitted_locale[1]);
    }
}
