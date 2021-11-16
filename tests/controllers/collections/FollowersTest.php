<?php

namespace flusio\controllers\collections;

use flusio\models;

class FollowersTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $owner_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
            'name' => $collection_name,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/follow");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/followers/show.phtml');
        $this->assertResponseContains($response, "Follow “{$collection_name}” on flusio");
    }

    public function testShowRedirectsIfConnected()
    {
        $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/follow");

        $this->assertResponseCode($response, 302, "/collections/{$collection_id}");
    }

    public function testShowFailsIfCollectionIsInaccessible()
    {
        $owner_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 0,
            'name' => $collection_name,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/follow");

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfCollectionDoesNotExist()
    {
        $owner_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
            'name' => $collection_name,
        ]);

        $response = $this->appRun('get', '/collections/not-an-id/follow');

        $this->assertResponseCode($response, 404);
    }

    public function testCreateMakesUserFollowingAndRedirects()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $this->assertSame(0, models\FollowedCollection::count());

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponse($response, 302, $from);
        $this->assertSame(1, models\FollowedCollection::count());
        $followed_collection = models\FollowedCollection::take();
        $this->assertSame($user->id, $followed_collection->user_id);
        $this->assertSame($collection_id, $followed_collection->collection_id);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponse($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testCreateFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', '/collections/unknown/follow', [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testCreateFailsIfUserHasNoAccess()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/follow", [
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponse($response, 302, $from);
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testDeleteMakesUserUnfollowingAndRedirects()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponse($response, 302, $from);
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user_id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponse($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(1, models\FollowedCollection::count());
    }

    public function testDeleteFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', '/collections/unknown/unfollow', [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(1, models\FollowedCollection::count());
    }

    public function testDeleteFailsIfUserHasNoAccess()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(1, models\FollowedCollection::count());
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/unfollow", [
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponse($response, 302, $from);
        $this->assertFlash('error', 'A security verification failed: you should retry to submit the form.');
        $this->assertSame(1, models\FollowedCollection::count());
    }
}
