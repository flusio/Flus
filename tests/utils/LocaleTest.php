<?php

namespace flusio\utils;

class LocaleTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider englishAcceptLanguage
     */
    public function testBestWithEnglish(string $accept_language): void
    {
        $locale = Locale::best($accept_language);

        $this->assertSame('en_GB', $locale);
    }

    /**
     * @dataProvider frenchAcceptLanguage
     */
    public function testBestWithFrench(string $accept_language): void
    {
        $locale = Locale::best($accept_language);

        $this->assertSame('fr_FR', $locale);
    }

    /**
     * @return array<array{string}>
     */
    public static function englishAcceptLanguage(): array
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

    /**
     * @return array<array{string}>
     */
    public static function frenchAcceptLanguage(): array
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
