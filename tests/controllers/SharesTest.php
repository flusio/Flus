<?php

namespace App\controllers;

class SharesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testNewRedirectosToLinkSearchWithUrl(): void
    {
        $url = 'https://flus.fr';

        $response = $this->appRun('GET', '/share', [
            'url' => $url,
        ]);

        $encoded_url = urlencode($url);
        $this->assertResponseCode($response, 302, "/links/search?url={$encoded_url}&autosubmit=1");
    }

    public function testNewRedirectosToLinkSearchWithText(): void
    {
        $url = 'https://flus.fr';

        $response = $this->appRun('GET', '/share', [
            'text' => $url,
        ]);

        $encoded_url = urlencode($url);
        $this->assertResponseCode($response, 302, "/links/search?url={$encoded_url}&autosubmit=1");
    }
}
