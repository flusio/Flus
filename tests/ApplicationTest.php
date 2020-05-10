<?php

namespace flusio;

use PHPUnit\Framework\TestCase;

class ApplicationTest extends TestCase
{
    /**
     * @after
     */
    public function resetSession()
    {
        session_unset();
    }

    public function testRunSetsTheDefaultLocale()
    {
        $request = new \Minz\Request('GET', '/');

        $application = new Application();
        $response = $application->run($request);

        $variables = \Minz\Output\View::defaultVariables();
        $this->assertSame('en_GB', $variables['current_locale']);
        $this->assertSame('en_GB', utils\Locale::currentLocale());
    }

    public function testRunSetsTheLocaleFromSessionLocale()
    {
        $_SESSION['locale'] = 'fr_FR';
        $request = new \Minz\Request('GET', '/');

        $application = new Application();
        $response = $application->run($request);

        $variables = \Minz\Output\View::defaultVariables();
        $this->assertSame('fr_FR', $variables['current_locale']);
        $this->assertSame('fr_FR', utils\Locale::currentLocale());
    }
}
