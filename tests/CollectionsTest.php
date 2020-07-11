<?php

namespace flusio;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $collection_name_1 = $this->fake('words', 3, true);
        $collection_name_2 = $this->fake('words', 3, true);
        $this->create('collection', [
            'user_id' => $user->id,
            'name' => $collection_name_1,
            'type' => 'collection',
        ]);
        $this->create('collection', [
            'user_id' => $user->id,
            'name' => $collection_name_2,
            'type' => 'collection',
        ]);

        $response = $this->appRun('get', '/collections');

        $this->assertResponse($response, 200);
        $response_output = $response->render();
        $this->assertStringContainsString($collection_name_1, $response_output);
        $this->assertStringContainsString($collection_name_2, $response_output);
        $this->assertPointer($response, 'collections/index.phtml');
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/collections');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fcollections');
    }

    public function testNewRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/collections/new');

        $this->assertResponse($response, 200, 'New collection');
        $this->assertPointer($response, 'collections/new.phtml');
    }

    public function testNewRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/collections/new');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
    }

    public function testCreateCreatesCollectionAndRedirects()
    {
        $user = $this->login();
        $collection_dao = new models\dao\Collection();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');

        $this->assertSame(0, $collection_dao->count());

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertSame(1, $collection_dao->count());
        $db_collection = $collection_dao->listAll()[0];
        $this->assertResponse($response, 302, "/collections/{$db_collection['id']}");
        $this->assertSame($name, $db_collection['name']);
        $this->assertSame($description, $db_collection['description']);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $collection_dao = new models\dao\Collection();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
        $this->assertSame(0, $collection_dao->count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $collection_dao = new models\dao\Collection();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => 'not the token',
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, $collection_dao->count());
    }

    public function testCreateFailsIfNameIsInvalid()
    {
        $user = $this->login();
        $collection_dao = new models\dao\Collection();
        $name = $this->fake('words', 100, true);
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponse($response, 400, 'The name must be less than 100 characters');
        $this->assertSame(0, $collection_dao->count());
    }

    public function testCreateFailsIfNameIsMissing()
    {
        $user = $this->login();
        $collection_dao = new models\dao\Collection();
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'description' => $description,
        ]);

        $this->assertResponse($response, 400, 'The name is required');
        $this->assertSame(0, $collection_dao->count());
    }

    public function testShowRendersCorrectly()
    {
        $user = $this->login();
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}");

        $this->assertResponse($response, 200, $link_title);
        $this->assertPointer($response, 'collections/show.phtml');
    }

    public function testShowRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}");
    }

    public function testShowFailsIfCollectionDoesNotExist()
    {
        $this->login();

        $response = $this->appRun('get', '/collections/unknown');

        $this->assertResponse($response, 404);
    }

    public function testShowFailsIfCollectionIsNotOwnedByCurrentUser()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}");

        $this->assertResponse($response, 404);
    }

    public function testShowFailsIfCollectionIsNotOfCorrectType()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}");

        $this->assertResponse($response, 404);
    }

    public function testShowBookmarksRendersCorrectly()
    {
        $user = $this->login();
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/bookmarks');

        $this->assertResponse($response, 200, $link_title);
        $this->assertPointer($response, 'collections/show_bookmarks.phtml');
    }

    public function testShowBookmarksRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/bookmarks');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarks');
    }

    public function testShowBookmarksFailsIfCollectionDoesNotExist()
    {
        $this->login();

        $response = $this->appRun('get', '/bookmarks');

        $this->assertResponse($response, 404, 'It looks like you have no “Bookmarks” collection');
    }
}
