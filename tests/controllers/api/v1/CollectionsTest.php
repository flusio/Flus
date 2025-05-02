<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\GroupFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testIndexReturnsCollectionsOfUser(): void
    {
        $user = $this->login();
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => 'My group',
        ]);
        $collection1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My favourites',
            'description' => 'My favourite links',
            'is_public' => true,
        ]);
        $collection2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My shares',
            'description' => 'My shared links',
            'group_id' => $group->id,
            'is_public' => false,
        ]);

        $response = $this->apiRun('GET', '/api/v1/collections');

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, [
            $collection1->toJson($user),
            $collection2->toJson($user),
        ]);
    }

    public function testIndexFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My favourites',
            'description' => 'My favourite links',
            'is_public' => true,
        ]);
        $collection2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => 'My shares',
            'description' => 'My shared links',
            'is_public' => false,
        ]);

        $response = $this->apiRun('GET', '/api/v1/collections');

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testCreateCreatesACollection(): void
    {
        $user = $this->login();
        $name = 'My collection';
        $description = 'A description';
        $is_public = true;

        $this->assertSame(0, models\Collection::count());

        $response = $this->apiRun('POST', '/api/v1/collections', [
            'name' => $name,
            'description' => $description,
            'is_public' => $is_public,
        ]);

        $this->assertSame(1, models\Collection::count());

        $this->assertResponseCode($response, 200);
        $collection = models\Collection::take();
        $this->assertNotNull($collection);
        $this->assertApiResponse($response, $collection->toJson($user));
    }

    public function testCreateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        $name = '';
        $description = 'A description';
        $is_public = true;

        $this->assertSame(0, models\Collection::count());

        $response = $this->apiRun('POST', '/api/v1/collections', [
            'name' => $name,
            'description' => $description,
            'is_public' => $is_public,
        ]);

        $this->assertSame(0, models\Collection::count());

        $this->assertResponseCode($response, 400);
        $this->assertApiError(
            $response,
            'name',
            ['presence', 'The name is required.']
        );
    }

    public function testCreateFailsIfNameIsTooLong(): void
    {
        $user = $this->login();
        $name = str_repeat('a', models\Collection::NAME_MAX_LENGTH + 1);
        $description = 'A description';
        $is_public = true;

        $this->assertSame(0, models\Collection::count());

        $response = $this->apiRun('POST', '/api/v1/collections', [
            'name' => $name,
            'description' => $description,
            'is_public' => $is_public,
        ]);

        $this->assertSame(0, models\Collection::count());

        $this->assertResponseCode($response, 400);
        $this->assertApiError(
            $response,
            'name',
            ['length', 'The name must be less than 100 characters.']
        );
    }

    public function testCreateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $name = 'My collection';
        $description = 'A description';
        $is_public = true;

        $this->assertSame(0, models\Collection::count());

        $response = $this->apiRun('POST', '/api/v1/collections', [
            'name' => $name,
            'description' => $description,
            'is_public' => $is_public,
        ]);

        $this->assertSame(0, models\Collection::count());

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testShowReturnsTheCollection(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'type' => 'collection',
            'name' => 'My shares',
            'description' => 'My shared links',
            'is_public' => true,
        ]);

        $response = $this->apiRun('GET', "/api/v1/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, $collection->toJson($user));
    }

    public function testShowFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'type' => 'collection',
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
            'type' => 'collection',
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
            'type' => 'collection',
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

    public function testUpdateUpdatesTheCollection(): void
    {
        $user = $this->login();
        $old_name = 'My collection';
        $old_description = 'A description';
        $old_is_public = true;
        $new_name = 'The collection';
        $new_description = 'The description';
        $new_is_public = false;
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_is_public,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/collections/{$collection->id}", [
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_is_public,
        ]);

        $this->assertResponseCode($response, 200);
        $collection = $collection->reload();
        $this->assertSame($new_name, $collection->name);
        $this->assertSame($new_description, $collection->description);
        $this->assertSame($new_is_public, $collection->is_public);
        $this->assertApiResponse($response, $collection->toJson($user));
    }

    public function testUpdateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        $old_name = 'My collection';
        $old_description = 'A description';
        $old_is_public = true;
        $new_name = '';
        $new_description = 'The description';
        $new_is_public = false;
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_is_public,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/collections/{$collection->id}", [
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_is_public,
        ]);

        $this->assertResponseCode($response, 400);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
        $this->assertSame($old_is_public, $collection->is_public);
        $this->assertApiError(
            $response,
            'name',
            ['presence', 'The name is required.']
        );
    }

    public function testUpdateFailsIfNameIsTooLong(): void
    {
        $user = $this->login();
        $old_name = 'My collection';
        $old_description = 'A description';
        $old_is_public = true;
        $new_name = str_repeat('a', models\Collection::NAME_MAX_LENGTH + 1);
        $new_description = 'The description';
        $new_is_public = false;
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_is_public,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/collections/{$collection->id}", [
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_is_public,
        ]);

        $this->assertResponseCode($response, 400);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
        $this->assertSame($old_is_public, $collection->is_public);
        $this->assertApiError(
            $response,
            'name',
            ['length', 'The name must be less than 100 characters.']
        );
    }

    public function testUpdateFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $old_name = 'My collection';
        $old_description = 'A description';
        $old_is_public = true;
        $new_name = 'The collection';
        $new_description = 'The description';
        $new_is_public = false;
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_is_public,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/collections/{$collection->id}", [
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_is_public,
        ]);

        $this->assertResponseCode($response, 403);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
        $this->assertSame($old_is_public, $collection->is_public);
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the collection.',
        ]);
    }

    public function testUpdateFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $old_name = 'My collection';
        $old_description = 'A description';
        $old_is_public = true;
        $new_name = 'The collection';
        $new_description = 'The description';
        $new_is_public = false;
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_is_public,
        ]);

        $response = $this->apiRun('PATCH', '/api/v1/collections/not-an-id', [
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_is_public,
        ]);

        $this->assertResponseCode($response, 404);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
        $this->assertSame($old_is_public, $collection->is_public);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
    }

    public function testUpdateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $old_name = 'My collection';
        $old_description = 'A description';
        $old_is_public = true;
        $new_name = 'The collection';
        $new_description = 'The description';
        $new_is_public = false;
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_is_public,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/collections/{$collection->id}", [
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_is_public,
        ]);

        $this->assertResponseCode($response, 401);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
        $this->assertSame($old_is_public, $collection->is_public);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testDeleteRemovesTheCollection(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertFalse(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => UserFactory::create()->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/collections/{$collection->id}");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot delete the collection.',
        ]);
        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/collections/not-an-id");

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/collections/{$collection->id}");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
        $this->assertTrue(models\Collection::exists($collection->id));
    }
}
