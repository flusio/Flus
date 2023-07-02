<?php

namespace flusio\controllers\collections;

class DiscoveryTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRedirects()
    {
        $response = $this->appRun('GET', '/collections/discover');

        $this->assertResponseCode($response, 302, '/discovery');
    }
}
