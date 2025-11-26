<?php

namespace App\controllers\links;

use App\forms;
use App\models;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class RepairingTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\HttpHelper;
    use \tests\LoginHelper;

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/repair");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'links/repairing/new.html.twig');
        $this->assertResponseContains($response, $url);
    }

    public function testNewRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/repair");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Frepair");
    }

    public function testNewFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', '/links/not-an-id/repair');

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfTheUserHasNoAccessToTheLink(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/repair");

        $this->assertResponseCode($response, 403);
    }

    public function testCreateChangesTheUrlAndRedirect(): void
    {
        $user = $this->login();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        $new_url = 'https://flus.fr/carnet/index.html';
        /** @var string */
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
            'csrf_token' => $this->csrfToken(forms\links\RepairLink::class),
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/repair");
        $link = $link->reload();
        $this->assertSame($new_url, $link->url);
        $this->assertSame($old_title, $link->title);
        $this->assertSame($old_reading_time, $link->reading_time);
        $this->assertSame($old_illustration, $link->image_filename);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateResynchronizesTheInfoIfForced(): void
    {
        $user = $this->login();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        $new_url = 'https://flus.fr/carnet/index.html';
        /** @var string */
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
            'csrf_token' => $this->csrfToken(forms\links\RepairLink::class),
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/repair");
        $link = $link->reload();
        $this->assertSame($new_url, $link->url);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(0, $link->reading_time);
        $this->assertNotSame($old_illustration, $link->image_filename);
    }

    public function testCreateAddsTheOldUrlToTheNeverList(): void
    {
        $user = $this->login();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        $new_url = 'https://flus.fr/carnet/index.html';
        /** @var string */
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
            'csrf_token' => $this->csrfToken(forms\links\RepairLink::class),
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/repair");
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

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        /** @var string */
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf_token' => $this->csrfToken(forms\links\RepairLink::class),
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Frepair");
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        /** @var string */
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', '/links/not-an-id/repair', [
            'url' => $new_url,
            'force_sync' => false,
            'csrf_token' => $this->csrfToken(forms\links\RepairLink::class),
        ]);

        $this->assertResponseCode($response, 404);
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheUserHasNoAccessToTheLink(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        /** @var string */
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf_token' => $this->csrfToken(forms\links\RepairLink::class),
        ]);

        $this->assertResponseCode($response, 403);
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        /** @var string */
        $new_url = $this->fakeUnique('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/repairing/new.html.twig');
        $this->assertResponseContains($response, 'A security verification failed');
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }

    public function testCreateFailsIfTheUrlIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $old_url = $this->fakeUnique('url');
        $new_url = 'ftp://example.com';
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $old_url,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/repair", [
            'url' => $new_url,
            'force_sync' => false,
            'csrf_token' => $this->csrfToken(forms\links\RepairLink::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/repairing/new.html.twig');
        $this->assertResponseContains($response, 'The link is invalid.');
        $link = $link->reload();
        $this->assertSame($old_url, $link->url);
    }
}
