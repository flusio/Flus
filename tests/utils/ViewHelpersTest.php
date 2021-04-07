<?php

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
class ViewHelpersTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @beforeClass
     */
    public static function loadViewHelpers()
    {
        // This is done in the src/Application.php file normally
        include_once(\Minz\Configuration::$app_path . '/src/utils/view_helpers.php');
    }

    public function testLocaleToBcp47TransformsLocale()
    {
        $locale = 'en_GB';

        $bcp47 = localeToBCP47($locale);

        $this->assertSame('en-GB', $bcp47);
    }

    public function testLocaleToBcp47TransformsLocaleIfNoUnderscore()
    {
        $locale = 'en';

        $bcp47 = localeToBCP47($locale);

        $this->assertSame('en', $bcp47);
    }
}
