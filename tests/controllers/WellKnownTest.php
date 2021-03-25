<?php

namespace flusio\controllers;

class WellKnownTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testChangePasswordRedirectsToSecurity()
    {
        $response = $this->appRun('GET', '/.well-known/change-password');

        $this->assertResponse($response, 302, '/my/security');
    }
}
