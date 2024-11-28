<?php

namespace App\controllers\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;

    #[\PHPUnit\Framework\Attributes\Before]
    public function emptyCachePath(): void
    {
        $files = glob(\App\Configuration::$application['cache_path'] . '/*');

        assert($files !== false);

        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/links/new.phtml');
        $this->assertResponseContains($response, 'New link');
    }

    public function testNewWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/links/new.phtml');
        $this->assertResponseContains($response, 'New link');
    }

    public function testNewRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testNewFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', '/collections/not-an-id/links/new', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('GET', "/collections/{$collection->id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testCreateCreatesLinkAndRedirects(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $this->assertResponseCode($response, 302, $from);
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
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'is_hidden' => true,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $this->assertResponseCode($response, 302, $from);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => 'a token',
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => 'not the token',
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
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
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
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'from' => $from,
            'csrf' => $user->csrf,
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
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', '/collections/not-an-id/links/new', [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
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
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $url = 'https://flus.fr/carnet/';
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('POST', "/collections/{$collection->id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Link::count());
    }
}
