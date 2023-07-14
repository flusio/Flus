<?php

namespace flusio\controllers;

class WellKnownTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testChangePasswordRedirectsToSecurity(): void
    {
        $response = $this->appRun('GET', '/.well-known/change-password');

        $this->assertResponseCode($response, 302, '/my/security');
    }
}
