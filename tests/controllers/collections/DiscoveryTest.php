<?php

namespace flusio\controllers\collections;

class DiscoveryTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRedirects()
    {
        $response = $this->appRun('get', '/collections/discover');

        $this->assertResponseCode($response, 302, '/discovery');
    }
}
