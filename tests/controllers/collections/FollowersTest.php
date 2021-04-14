<?php

namespace flusio\controllers\collections;

use flusio\models;

class FollowersTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testCreateMakesUserFollowingAndRedirects()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);

        $this->assertSame(0, $followed_collection_dao->count());

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, "/collections/{$collection_id}");
        $this->assertSame(1, $followed_collection_dao->count());
        $db_followed_collection = $followed_collection_dao->listAll()[0];
        $this->assertSame($user->id, $db_followed_collection['user_id']);
        $this->assertSame($collection_id, $db_followed_collection['collection_id']);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}");
        $this->assertSame(0, $followed_collection_dao->count());
    }

    public function testCreateFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);

        $response = $this->appRun('post', '/collections/unknown/follow', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(0, $followed_collection_dao->count());
    }

    public function testCreateFailsIfUserHasNoAccess()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 0,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(0, $followed_collection_dao->count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, "/collections/{$collection_id}");
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $this->assertSame(0, $followed_collection_dao->count());
    }

    public function testDeleteMakesUserUnfollowingAndRedirects()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, "/collections/{$collection_id}");
        $this->assertSame(0, $followed_collection_dao->count());
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => 'a token',
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}");
        $this->assertSame(1, $followed_collection_dao->count());
    }

    public function testDeleteFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', '/collections/unknown/unfollow', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(1, $followed_collection_dao->count());
    }

    public function testDeleteFailsIfUserHasNoAccess()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(1, $followed_collection_dao->count());
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $followed_collection_dao = new models\dao\FollowedCollection();
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponse($response, 302, "/collections/{$collection_id}");
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $this->assertSame(1, $followed_collection_dao->count());
    }
}
