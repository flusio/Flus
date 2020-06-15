<?php

namespace flusio;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowBookmarkedRendersCorrectly()
    {
        $user = $this->login();
        $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarked',
        ]);

        $response = $this->appRun('get', '/bookmarked');

        $this->assertResponse($response, 200, 'You have no links here yet.');
        $this->assertPointer($response, 'collections/show_bookmarked.phtml');
    }

    public function testShowBookmarkedRendersCorrectlyIfCollectionDoesNotExist()
    {
        $this->login();

        $response = $this->appRun('get', '/bookmarked');

        $this->assertResponse($response, 200, 'You never used the “Read Later” collection.');
        $this->assertPointer($response, 'collections/show_bookmarked.phtml');
    }

    public function testShowBookmarkedRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/bookmarked');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarked');
    }

    public function testCreateBookmarkedCreatesCollection()
    {
        $collection_dao = new models\dao\Collection();

        $user = $this->login();

        $this->assertSame(0, $collection_dao->count());

        $response = $this->appRun('post', '/bookmarked', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 201);
        $this->assertPointer($response, 'collections/show_bookmarked.phtml');
        $this->assertSame(1, $collection_dao->count());
        $db_collection = $collection_dao->listAll()[0];
        $this->assertSame('bookmarked', $db_collection['type']);
        $this->assertSame($user->id, $db_collection['user_id']);
    }

    public function testCreateBookmarkedRedirectsIfNotConnected()
    {
        $collection_dao = new models\dao\Collection();

        $response = $this->appRun('post', '/bookmarked', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fbookmarked');
        $this->assertSame(0, $collection_dao->count());
    }

    public function testCreateBookmarkedRedirectsIfItAlreadyExists()
    {
        $collection_dao = new models\dao\Collection();

        $user = $this->login();
        $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarked',
        ]);

        $this->assertSame(1, $collection_dao->count());

        $response = $this->appRun('post', '/bookmarked', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
        ]);

        $this->assertResponse($response, 302, '/bookmarked');
        $this->assertSame(1, $collection_dao->count());
    }

    public function testCreateBookmarkedFailsIfCsrfIsInvalid()
    {
        $collection_dao = new models\dao\Collection();

        $user = $this->login();

        $response = $this->appRun('post', '/bookmarked', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, $collection_dao->count());
    }
}
