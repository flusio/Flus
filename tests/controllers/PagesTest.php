<?php

namespace flusio\controllers;

class PagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testHomeRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponseCode($response, 302, '/login');
    }

    public function testHomeRedirectsToNewsIfConnected()
    {
        $this->login();

        $response = $this->appRun('GET', '/');

        $this->assertResponseCode($response, 302, '/news');
    }

    public function testTermsRendersCorrectlyWhenTermsExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));

        $response = $this->appRun('GET', '/terms');

        @unlink($terms_path);
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Terms of service');
    }

    public function testTermsFailsIfTermsDoNotExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        @unlink($terms_path);

        $response = $this->appRun('GET', '/terms');

        $this->assertResponseCode($response, 404);
    }

    public function testTermsRendersCorrectly()
    {
        $response = $this->appRun('GET', '/about');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'pages/about.phtml');
        $this->assertResponseContains($response, 'About flusio');
    }
}
