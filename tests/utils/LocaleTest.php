<?php

namespace flusio\utils;

class LocaleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider englishAcceptLanguage
     */
    public function testBestWithEnglish($accept_language)
    {
        $locale = Locale::best($accept_language);

        $this->assertSame('en_GB', $locale);
    }

    /**
     * @dataProvider frenchAcceptLanguage
     */
    public function testBestWithFrench($accept_language)
    {
        $locale = Locale::best($accept_language);

        $this->assertSame('fr_FR', $locale);
    }

    public function englishAcceptLanguage()
    {
        return [
            [''],
            ['àà'],
            ['en'],
            ['en-US'],
            ['en, fr-FR;q=0.8'],
            ['en-US, en;q=0.8'],
            ['de_DE'],
        ];
    }

    public function frenchAcceptLanguage()
    {
        return [
            ['fr'],
            ['fr-BE'],
            ['fr-CA'],
            ['fr-FR'],
            ['fr-LU'],
            ['fr-CH'],
            ['fr, fr-FR;q=0.8'],
            ['fr,en;q = 0.8'],
            ['en;q=0.8, fr'],
            ['en ; q = 0.8, fr-FR ; q = 0.9'],
            ['en;q=0.900, fr-fr;q=0.901'],
            ['fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3'],
        ];
    }
}
