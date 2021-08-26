<?php

namespace flusio\controllers;

class PagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testHomeRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('GET', '/');

        $this->assertResponse($response, 302, '/login');
    }

    public function testHomeRedirectsToNewsIfConnected()
    {
        $this->login();

        $response = $this->appRun('GET', '/');

        $this->assertResponse($response, 302, '/news');
    }

    public function testTermsRendersCorrectlyWhenTermsExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        file_put_contents($terms_path, $this->fake('sentence'));

        $response = $this->appRun('GET', '/terms');

        @unlink($terms_path);
        $this->assertResponse($response, 200, 'Terms of service');
    }

    public function testTermsFailsIfTermsDoNotExist()
    {
        $app_path = \Minz\Configuration::$app_path;
        $terms_path = $app_path . '/policies/terms.html';
        @unlink($terms_path);

        $response = $this->appRun('GET', '/terms');

        $this->assertResponse($response, 404);
    }
}
