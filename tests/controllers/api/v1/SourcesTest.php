<?php

namespace App\controllers\api\v1;

use tests\factories\CollectionFactory;
use tests\factories\FeedFactory;
use tests\factories\UserFactory;

class SourcesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testShowReturnsTheSource(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'name' => 'My shares',
            'description' => 'My shared links',
            'is_public' => true,
        ]);

        $response = $this->apiRun('GET', "/api/v1/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, $collection->toJson($user));
    }

    public function testShowReturnsTheNameGivenByTheUser(): void
    {
        $user = $this->login();
        $feed = FeedFactory::create([
            'name' => 'Carnet de Flus',
            'is_public' => true,
        ]);
        $follow = $user->follow($feed);
        $follow->name = 'Flus news';
        $follow->save();

        $response = $this->apiRun('GET', "/api/v1/collections/{$feed->id}");

        $this->assertResponseCode($response, 200);
        $json = $this->responseToJson($response);
        $this->assertSame('Flus news', $json['name']);
    }

    public function testShowFailsIfTheSourceDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'name' => 'My shares',
            'description' => 'My shared links',
            'is_public' => true,
        ]);

        $response = $this->apiRun('GET', '/api/v1/collections/not-an-id');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
    }

    public function testShowFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'name' => 'My shares',
            'description' => 'My shared links',
            'is_public' => false,
        ]);

        $response = $this->apiRun('GET', "/api/v1/collections/{$collection->id}");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot access the collection.',
        ]);
    }

    public function testShowFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'name' => 'My shares',
            'description' => 'My shared links',
            'is_public' => false,
        ]);

        $response = $this->apiRun('GET', "/api/v1/collections/{$collection->id}");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
