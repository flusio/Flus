<?php

namespace App\controllers\api\v1\collections;

use tests\factories\CollectionFactory;
use tests\factories\UserFactory;

class FollowTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateMakesUserFollowTheCollection(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $this->assertFalse($user->isFollowing($collection->id));

        $response = $this->apiRun('POST', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->isFollowing($collection->id));
    }

    public function testCreateFailsIfTheCollectionIsNotAccessible(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => false,
        ]);

        $this->assertFalse($user->isFollowing($collection->id));

        $response = $this->apiRun('POST', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->isFollowing($collection->id));
        $this->assertApiResponse($response, [
            'error' => 'You cannot follow the collection.',
        ]);
    }

    public function testCreateFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->apiRun('POST', '/api/v1/collections/not-an-id/follow');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
    }

    public function testCreateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $this->assertFalse($user->isFollowing($collection->id));

        $response = $this->apiRun('POST', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 401);
        $this->assertFalse($user->isFollowing($collection->id));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testDeleteMakesUserUnfollowTheCollection(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($collection->id);

        $this->assertTrue($user->isFollowing($collection->id));

        $response = $this->apiRun('DELETE', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 200);
        $this->assertFalse($user->isFollowing($collection->id));
    }

    public function testDeleteFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();

        $response = $this->apiRun('DELETE', '/api/v1/collections/not-an-id/follow');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
    }

    public function testDeleteFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($collection->id);

        $this->assertTrue($user->isFollowing($collection->id));

        $response = $this->apiRun('DELETE', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 401);
        $this->assertTrue($user->isFollowing($collection->id));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
