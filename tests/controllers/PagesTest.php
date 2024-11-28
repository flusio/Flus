<?php

namespace App\controllers;

class PagesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testHomeRedirectsToLoginIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponseCode($response, 302, '/login');
    }

    public function testHomeRedirectsToNewsIfConnected(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/');

        $this->assertResponseCode($response, 302, '/news');
    }

    public function testTermsRendersCorrectlyWhenTermsExist(): void
    {
        $app_path = \App\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));

        $response = $this->appRun('GET', '/terms');

        @unlink($terms_path);
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Terms of service');
    }

    public function testTermsFailsIfTermsDoNotExist(): void
    {
        $app_path = \App\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        @unlink($terms_path);

        $response = $this->appRun('GET', '/terms');

        $this->assertResponseCode($response, 404);
    }

    public function testAddonsRendersCorrectly(): void
    {
        $response = $this->appRun('GET', '/addons');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'pages/addons.phtml');
        $this->assertResponseContains($response, ' Keep Flus at hand in your browser');
    }

    public function testAboutRendersCorrectly(): void
    {
        $response = $this->appRun('GET', '/about');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'pages/about.phtml');
        $this->assertResponseContains($response, 'About Flus');
    }

    public function testRobotsRendersCorrectlyWhenRegistrationsAreOpened(): void
    {
        \App\Configuration::$application['registrations_opened'] = true;

        $response = $this->appRun('GET', '/robots.txt');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'pages/robots.txt');
        $this->assertResponseEquals($response, <<<TXT
            User-agent: *
            Allow: /

            TXT);
    }

    public function testRobotsRendersCorrectlyWhenRegistrationsAreClosed(): void
    {
        \App\Configuration::$application['registrations_opened'] = false;

        $response = $this->appRun('GET', '/robots.txt');

        \App\Configuration::$application['registrations_opened'] = true;

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'pages/robots.txt');
        $this->assertResponseEquals($response, <<<TXT
            User-agent: *
            Disallow: /

            TXT);
    }
}
