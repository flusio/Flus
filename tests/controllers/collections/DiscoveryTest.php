<?php

namespace App\controllers\collections;

class DiscoveryTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRedirects(): void
    {
        $response = $this->appRun('GET', '/collections/discover');

        $this->assertResponseCode($response, 302, '/discovery');
    }
}
