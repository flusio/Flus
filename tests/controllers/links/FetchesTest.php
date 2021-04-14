<?php

namespace flusio\controllers\links;

use flusio\models;

class FetchesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @before
     */
    public function emptyCachePath()
    {
        $files = glob(\Minz\Configuration::$application['cache_path'] . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testShowRendersCorrectly()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'fetched_at' => null,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 200, 'Please wait');
        $this->assertPointer($response, 'links/fetches/show.phtml');
    }

    public function testShowWithFetchedLinkRendersCorrectly()
    {
        $title = $this->fake('words', 3, true);
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $title,
            'fetched_at' => $this->fake('iso8601'),
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 200, $title);
    }

    public function testShowFailsIfNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'fetched_at' => null,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Ffetch");
    }

    public function testShowFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/links/not-a-valid-id/fetch');

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
    }

    public function testShowFailsIfUserDoesNotOwnTheLink()
    {
        $current_user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/fetch");

        $this->assertResponse($response, 404, 'This page doesn’t exist.');
    }

    public function testCreateUpdatesLinkWithTheTitleAndRedirects()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $link = models\Link::find($link_id);
        $this->assertSame('flusio/flusio', $link->title);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateSavesResponseInCache()
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $url,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $hash = \SpiderBits\Cache::hash($url);
        $cache_filepath = \Minz\Configuration::$application['cache_path'] . '/' . $hash;
        $this->assertTrue(file_exists($cache_filepath));
    }

    public function testCreateUsesCache()
    {
        $user = $this->login();
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $url,
        ]);
        $expected_title = 'The foo bar baz';
        $hash = \SpiderBits\Cache::hash($url);
        $raw_response = <<<TEXT
        HTTP/2 200 OK
        Content-Type: text/html

        <html>
            <head>
                <title>{$expected_title}</title>
            </head>
        </html>
        TEXT;
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $link = models\Link::find($link_id);
        $this->assertSame($expected_title, $link->title);
    }

    public function testCreateFetchesTwitterCorrectly()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://twitter.com/flus_fr/status/1272070701193797634',
            'title' => 'https://twitter.com/flus_fr/status/1272070701193797634',
        ]);
        $expected_title = 'Flus on Twitter: “Parce que s’informer est un acte politique'
                        . ' essentiel, il est important de disposer des bons outils pour cela.'
                        . " Je développe #Flus, un média social citoyen.\nhttps://t.co/zDFwWVmaiD”";

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $link = models\Link::find($link_id);
        $this->assertSame($expected_title, $link->title);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateHandlesIso8859()
    {
        $fixtures_path = \Minz\Configuration::$app_path . '/tests/fixtures';
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $url,
        ]);
        $hash = \SpiderBits\Cache::hash($url);
        $raw_response = file_get_contents($fixtures_path . '/responses/test_iso_8859_1');
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $link = models\Link::find($link_id);
        $this->assertSame('Test ëéàçï', $link->title);
    }

    public function testCreateHandlesBadEncoding()
    {
        $fixtures_path = \Minz\Configuration::$app_path . '/tests/fixtures';
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $url,
        ]);
        $hash = \SpiderBits\Cache::hash($url);
        $raw_response = file_get_contents($fixtures_path . '/responses/test_bad_encoding');
        $cache = new \SpiderBits\Cache(\Minz\Configuration::$application['cache_path']);
        $cache->save($hash, $raw_response);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $link = models\Link::find($link_id);
        $this->assertSame(410, $link->fetched_code);
    }

    public function testCreateDownloadsOpenGraphIllustration()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://flus.fr/carnet/flus-media-social-citoyen.html',
            'image_filename' => null,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $link = models\Link::find($link_id);
        $image_filename = $link->image_filename;
        $this->assertNotNull($image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $card_filepath = "{$media_path}/cards/{$image_filename}";
        $large_filepath = "{$media_path}/large/{$image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }

    public function testCreateDoesNotChangeTitleIfUnreachable()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://flus.fr/does_not_exist.html',
            'title' => 'https://flus.fr/does_not_exist.html',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $link = models\Link::find($link_id);
        $expected_title = 'https://flus.fr/does_not_exist.html';
        $this->assertSame($expected_title, $link->title);
        $this->assertSame(404, $link->fetched_code);
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $link = models\Link::find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $link->title);
    }

    public function testCreateFailsIfNotConnected()
    {
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}%2Ffetch");
        $link = models\Link::find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $link->title);
    }

    public function testCreateFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();

        $response = $this->appRun('post', "/links/do-not-exist/fetch", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404, 'This page doesn’t exist');
    }

    public function testCreateFailsIfUserDoesNotOwnTheLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => 'https://github.com/flusio/flusio',
            'title' => 'https://github.com/flusio/flusio',
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/fetch", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404, 'This page doesn’t exist');
        $link = models\Link::find($link_id);
        $expected_title = 'https://github.com/flusio/flusio';
        $this->assertSame($expected_title, $link->title);
    }
}
