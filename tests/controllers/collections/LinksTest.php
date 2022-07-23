<?php

namespace flusio\controllers\collections;

use flusio\models;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
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

    public function testNewRendersCorrectly()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', "/collections/{$collection_id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/links/new.phtml');
        $this->assertResponseContains($response, 'New link');
    }

    public function testNewWorksIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', "/collections/{$collection_id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/links/new.phtml');
        $this->assertResponseContains($response, 'New link');
    }

    public function testNewRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', "/collections/{$collection_id}/links/new", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testNewFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', '/collections/not-an-id/links/new', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', "/collections/{$collection_id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfCollectionIsSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('get', "/collections/{$collection_id}/links/new", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testCreateCreatesLinkAndRedirects()
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(0, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $this->assertResponseCode($response, 302, $from);
        $link = models\Link::take();
        $this->assertSame($url, $link->url);
        $this->assertSame('Carnet de Flus', $link->title);
        $this->assertSame(200, $link->fetched_code);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
        $this->assertFalse($link->is_hidden);
    }

    public function testCreateAllowsToCreateHiddenLinks()
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'is_hidden' => true,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $link = models\Link::take();
        $this->assertTrue($link->is_hidden);
    }

    public function testCreateWorksIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $link = models\Link::take();
        $this->assertSame($url, $link->url);
        $this->assertSame($user->id, $link->user_id);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateDoesNotCreateLinkIfItExists()
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $this->assertSame(1, models\Link::count());
        $this->assertSame(0, models\LinkToCollection::count());

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertSame(1, models\Link::count());
        $this->assertSame(1, models\LinkToCollection::count());

        $this->assertResponseCode($response, 302, $from);
        $link = models\Link::take();
        $this->assertSame($link_id, $link->id);
        $this->assertContains($collection_id, array_column($link->collections(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => 'a token',
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid()
    {
        $user = $this->login();
        $url = 'an invalid URL';
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is invalid.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsMissing()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('post', '/collections/not-an-id/links/new', [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsNotShared()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCollectionIsSharedWithReadAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $url = 'https://flus.fr/carnet/';
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('bookmarks');

        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->appRun('post', "/collections/{$collection_id}/links/new", [
            'url' => $url,
            'from' => $from,
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Link::count());
    }
}
