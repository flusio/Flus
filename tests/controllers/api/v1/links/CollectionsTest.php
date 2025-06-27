<?php

namespace App\controllers\api\v1\links;

use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testCreateAddsTheCollectionToTheLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('PUT', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $link_collections = $link->collections();
        $this->assertSame(1, count($link_collections));
        $this->assertSame($collection->id, $link_collections[0]->id);
    }

    public function testCreateFailsIfTheLinkIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('PUT', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 403);
        $link_collections = $link->collections();
        $this->assertSame(0, count($link_collections));
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testCreateFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('PUT', "/api/v1/links/not-an-id/collections/{$collection->id}");

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testCreateFailsIfTheCollectionIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('PUT', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 403);
        $link_collections = $link->collections();
        $this->assertSame(0, count($link_collections));
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testCreateFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->apiRun('PUT', "/api/v1/links/{$link->id}/collections/not-an-id");

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
    }

    public function testCreateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('PUT', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 401);
        $link_collections = $link->collections();
        $this->assertSame(0, count($link_collections));
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testDeleteRemovesTheCollectionFromTheLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link->addCollection($collection);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $link_collections = $link->collections();
        $this->assertSame(0, count($link_collections));
    }

    public function testDeleteFailsIfTheLinkIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link->addCollection($collection);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 403);
        $link_collections = $link->collections();
        $this->assertSame(1, count($link_collections));
        $this->assertSame($collection->id, $link_collections[0]->id);
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testDeleteFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/links/not-an-id/collections/{$collection->id}");

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testDeleteFailsIfTheCollectionIsNotOwned(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'type' => 'collection',
        ]);
        $link->addCollection($collection);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 403);
        $link_collections = $link->collections();
        $this->assertSame(1, count($link_collections));
        $this->assertSame($collection->id, $link_collections[0]->id);
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testDeleteFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/collections/not-an-id");

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
    }

    public function testDeleteFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link->addCollection($collection);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}/collections/{$collection->id}");

        $this->assertResponseCode($response, 401);
        $link_collections = $link->collections();
        $this->assertSame(1, count($link_collections));
        $this->assertSame($collection->id, $link_collections[0]->id);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
