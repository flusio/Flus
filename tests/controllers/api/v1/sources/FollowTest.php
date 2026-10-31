<?php

namespace App\controllers\api\v1\sources;

use tests\factories\CollectionFactory;
use tests\factories\UserFactory;

class FollowTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateMakesUserFollowTheSource(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $this->assertFalse($user->isFollowing($collection));

        $response = $this->apiRun('POST', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 200);
        $this->assertTrue($user->isFollowing($collection));
    }

    public function testCreateFailsIfTheSourceIsNotAccessible(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);

        $this->assertFalse($user->isFollowing($collection));

        $response = $this->apiRun('POST', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 403);
        $this->assertFalse($user->isFollowing($collection));
        $this->assertApiResponse($response, [
            'error' => 'You cannot follow the collection.',
        ]);
    }

    public function testCreateFailsIfTheSourceDoesNotExist(): void
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
            'is_public' => true,
        ]);

        $this->assertFalse($user->isFollowing($collection));

        $response = $this->apiRun('POST', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 401);
        $this->assertFalse($user->isFollowing($collection));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testDeleteMakesUserUnfollowTheSource(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $this->assertTrue($user->isFollowing($collection));

        $response = $this->apiRun('DELETE', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 200);
        $this->assertFalse($user->isFollowing($collection));
    }

    public function testDeleteFailsIfTheSourceDoesNotExist(): void
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
            'is_public' => true,
        ]);
        $user->follow($collection);

        $this->assertTrue($user->isFollowing($collection));

        $response = $this->apiRun('DELETE', "/api/v1/collections/{$collection->id}/follow");

        $this->assertResponseCode($response, 401);
        $this->assertTrue($user->isFollowing($collection));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
