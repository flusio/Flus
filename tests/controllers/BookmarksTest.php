<?php

namespace App\controllers;

use App\forms;
use App\models;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class BookmarksTest extends \PHPUnit\Framework\TestCase
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

        $response = $this->appRun('GET', '/read/later/new');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'bookmarks/new.html.twig');
        $this->assertResponseContains($response, 'New link');
    }

    public function testNewRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();

        $response = $this->appRun('GET', '/read/later/new');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fread%2Flater%2Fnew');
    }

    public function testCreateCreatesLinkAndRedirects(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('POST', '/read/later/new', [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\links\NewLinkSimple::class),
        ]);

        $this->assertSame(1, models\Link::count());

        $this->assertResponseCode($response, 302, '/read/later/new');
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertSame($url, $link->url);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame($user->id, $link->user_id);
        $this->assertFalse($link->is_hidden);
        $this->assertTrue($user->hasReadLater($link));
    }

    public function testCreateAllowsToCreateHiddenLinks(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', '/read/later/new', [
            'url' => $url,
            'is_hidden' => true,
            'csrf_token' => $this->csrfToken(forms\links\NewLinkSimple::class),
        ]);

        $this->assertResponseCode($response, 302, '/read/later/new');
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertTrue($link->is_hidden);
        $this->assertTrue($user->hasReadLater($link));
    }

    public function testCreateDoesNotCreateLinkIfItExists(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('POST', '/read/later/new', [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\links\NewLinkSimple::class),
        ]);

        $this->assertSame(1, models\Link::count());

        $this->assertResponseCode($response, 302, '/read/later/new');
        $this->assertTrue($user->hasReadLater($link));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $url = 'https://flus.fr/carnet/';

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', '/read/later/new', [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\links\NewLinkSimple::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fread%2Flater%2Fnew');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', '/read/later/new', [
            'url' => $url,
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid(): void
    {
        $user = $this->login();
        $url = 'an invalid URL';

        $response = $this->appRun('POST', '/read/later/new', [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\links\NewLinkSimple::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is invalid.');
        $this->assertSame(0, models\Link::count());
    }

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $user->markAsReadLater($link);

        $response = $this->appRun('GET', '/bookmarks');

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'bookmarks/index.html.twig');
        $this->assertResponseContains($response, $link_title);
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/bookmarks');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
    }
}
