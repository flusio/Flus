<?php

namespace flusio\controllers\links;

use flusio\models;

class RepairingTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testNewRendersCorrectly()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/repair", [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/repairing/new.phtml');
        $this->assertResponseContains($response, $url);
    }

    public function testNewRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user');
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $url,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/repair", [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
    }

    public function testNewFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('get', '/links/not-an-id/repair', [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfTheUserHasNoAccessToTheLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = $this->fake('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $url,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/repair", [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testCreateChangesTheUrlAndRedirect()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/repair", [
            'url' => $new_url,
            'ask_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $link = models\Link::find($link_id);
        $this->assertSame($new_url, $link->url);
    }

    public function testCreateResynchronizesTheLinkIfAsked()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = 'https://flus.fr/carnet/index.html';
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $old_url,
            'image_filename' => 'old.png',
            'fetched_code' => 404,
        ]);
        $card_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFixture($new_url, 'responses/flus.fr_carnet_index.html');
        $this->mockHttpWithFile($card_url, 'public/static/og-card.png');

        $response = $this->appRun('post', "/links/{$link_id}/repair", [
            'url' => $new_url,
            'ask_sync' => true,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $link = models\Link::find($link_id);
        $this->assertSame($new_url, $link->url);
        $this->assertNotSame('old.png', $link->image_filename);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/repair", [
            'url' => $new_url,
            'ask_sync' => false,
            'csrf' => 'a token',
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $link = models\Link::find($link_id);
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('post', '/links/not-an-id/repair', [
            'url' => $new_url,
            'ask_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
        $link = models\Link::find($link_id);
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheUserHasNoAccessToTheLink()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/repair", [
            'url' => $new_url,
            'ask_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
        $link = models\Link::find($link_id);
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheCsrfIsInvalid()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/repair", [
            'url' => $new_url,
            'ask_sync' => false,
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'links/repairing/new.turbo_stream.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $link = models\Link::find($link_id);
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheUrlIsInvalid()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = 'ftp://example.com';
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('post', "/links/{$link_id}/repair", [
            'url' => $new_url,
            'ask_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'links/repairing/new.turbo_stream.phtml');
        $this->assertResponseContains($response, 'Link scheme must be either http or https.');
        $link = models\Link::find($link_id);
        $this->assertSame($old_url, $link->url);
    }
}
