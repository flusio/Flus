<?php

namespace flusio\controllers\links;

use flusio\models;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class RepairingTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testNewRendersCorrectly()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/repair", [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/repairing/new.phtml');
        $this->assertResponseContains($response, $url);
    }

    public function testNewRedirectsToLoginIfNotConnected()
    {
        $user = UserFactory::create();
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/repair", [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
    }

    public function testNewFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', '/links/not-an-id/repair', [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfTheUserHasNoAccessToTheLink()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/repair", [
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testCreateChangesTheUrlAndRedirect()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = 'https://flus.fr/carnet/index.html';
        $old_title = $this->fake('sentence');
        $old_reading_time = 9999;
        $old_illustration = 'old.png';
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
            'image_filename' => $old_illustration,
            'fetched_code' => 404,
        ]);
        $this->mockHttpWithFixture($new_url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $link = $link->reload();
        $this->assertSame($new_url, $link->url);
        $this->assertSame($old_title, $link->title);
        $this->assertSame($old_reading_time, $link->reading_time);
        $this->assertSame($old_illustration, $link->image_filename);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateResynchronizesTheInfoIfForced()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = 'https://flus.fr/carnet/index.html';
        $old_title = $this->fake('sentence');
        $old_reading_time = 9999;
        $old_illustration = 'old.png';
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
            'image_filename' => $old_illustration,
        ]);
        $card_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFixture($new_url, 'responses/flus.fr_carnet_index.html');
        $this->mockHttpWithFile($card_url, 'public/static/og-card.png');

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => true,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $link = $link->reload();
        $this->assertSame($new_url, $link->url);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(0, $link->reading_time);
        $this->assertNotSame($old_illustration, $link->image_filename);
    }

    public function testCreateAddsTheOldUrlToTheNeverList()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = 'https://flus.fr/carnet/index.html';
        $old_title = $this->fake('sentence');
        $old_reading_time = 9999;
        $old_illustration = 'old.png';
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
            'image_filename' => $old_illustration,
            'fetched_code' => 404,
        ]);
        $this->mockHttpWithFixture($new_url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $link = models\Link::findBy([
            'url' => $old_url,
        ]);
        $never_list = $user->neverList();
        $this->assertNotNull($link);
        $link_to_never_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $never_list->id,
        ]);
        $this->assertNotNull($link_to_never_list);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf' => 'a token',
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheLinkDoesNotExist()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', '/links/not-an-id/repair', [
            'url' => $new_url,
            'force_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheUserHasNoAccessToTheLink()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 404);
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheCsrfIsInvalid()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'links/repairing/new.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheUrlIsInvalid()
    {
        $user = $this->login();
        $old_url = $this->fakeUnique('url');
        $new_url = 'ftp://example.com';
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('home'),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'links/repairing/new.phtml');
        $this->assertResponseContains($response, 'The link is invalid.');
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }
}
