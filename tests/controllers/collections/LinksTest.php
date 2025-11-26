<?php

namespace App\controllers\collections;

use App\forms;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FilesystemHelper;
    use \tests\HttpHelper;
    use \tests\LoginHelper;

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/links/new.html.twig');
        $this->assertResponseContains($response, 'New link');
    }

    public function testNewWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/links/new.html.twig');
        $this->assertResponseContains($response, 'New link');
    }

    public function testNewRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new");

        $this->assertResponseCode(
            $response,
            302,
            "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Flinks%2Fnew"
        );
    }

    public function testNewFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('GET', '/collections/not-an-id/links/new');

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new");

        $this->assertResponseCode($response, 403);
    }

    public function testNewFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new");

        $this->assertResponseCode($response, 403);
    }

    public function testCreateCreatesLinkAndRedirects(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/links/new");
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertSame($url, $link->url);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
        $this->assertFalse($link->is_hidden);
    }

    public function testCreateAllowsToCreateHiddenLinks(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'is_hidden' => true,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/links/new");
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertTrue($link->is_hidden);
    }

    public function testCreateWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/links/new");
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertSame($url, $link->url);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateDoesNotCreateLinkIfItExists(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/links/new");
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode(
            $response,
            302,
            "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Flinks%2Fnew"
        );
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
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
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is invalid.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsMissing(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', '/collections/not-an-id/links/new', [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'csrf_token' => $this->csrfToken(forms\collections\AddLinkToCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\Link::count());
    }
}
