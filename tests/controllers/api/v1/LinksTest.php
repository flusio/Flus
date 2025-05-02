<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class LinksTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;
    use \tests\ApiHelper;

    public function testIndexReturnsTheLinksOfTheCollection(): void
    {
        $this->freeze();
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($collection, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection={$collection->id}");

        $this->assertResponseCode($response, 200);
        $link1->published_at = \Minz\Time::ago(5, 'minutes');
        $link2->published_at = \Minz\Time::ago(10, 'minutes');
        $this->assertApiResponse($response, [
            $link1->toJson($user),
            $link2->toJson($user),
        ]);
    }

    public function testIndexCanReturnTheReadLinks(): void
    {
        $this->freeze();
        $user = $this->login();
        $collection = $user->readList();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection=read");

        $this->assertResponseCode($response, 200);
        $link1->published_at = \Minz\Time::ago(5, 'minutes');
        $this->assertApiResponse($response, [
            $link1->toJson($user),
        ]);
    }

    public function testIndexCanReturnTheLinksToRead(): void
    {
        $this->freeze();
        $user = $this->login();
        $collection = $user->bookmarks();
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection=to-read");

        $this->assertResponseCode($response, 200);
        $link1->published_at = \Minz\Time::ago(5, 'minutes');
        $this->assertApiResponse($response, [
            $link1->toJson($user),
        ]);
    }

    public function testIndexPaginatesTheLinks(): void
    {
        $this->freeze();
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($collection, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection={$collection->id}&page=2&per_page=1");

        $this->assertResponseCode($response, 200);
        $link2->published_at = \Minz\Time::ago(10, 'minutes');
        $this->assertApiResponse($response, [
            $link2->toJson($user),
        ]);
    }

    public function testIndexDoesNotReturnHiddenLinksIfAccessIsNotGranted(): void
    {
        $this->freeze();
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link1 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($collection, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection={$collection->id}");

        $this->assertResponseCode($response, 200);
        $link2->published_at = \Minz\Time::ago(10, 'minutes');
        $this->assertApiResponse($response, [
            $link2->toJson($user),
        ]);
    }

    public function testIndexFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($collection, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', '/api/v1/links?collection=not-an-id');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The collection does not exist.',
        ]);
    }

    public function testIndexFailsIfThePageDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($collection, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection={$collection->id}&page=3&per_page=1");

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The page does not exist.',
        ]);
    }

    public function testIndexFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link1 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($collection, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection={$collection->id}");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot access the collection.',
        ]);
    }

    public function testIndexFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link1 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        $link2 = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        $link1->addCollection($collection, at: \Minz\Time::ago(5, 'minutes'));
        $link2->addCollection($collection, at: \Minz\Time::ago(10, 'minutes'));

        $response = $this->apiRun('GET', "/api/v1/links?collection={$collection->id}");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testShowReturnsTheLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => false,
        ]);

        $response = $this->apiRun('GET', "/api/v1/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, $link->toJson($user));
    }

    public function testShowFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => false,
        ]);

        $response = $this->apiRun('GET', '/api/v1/links/not-an-id');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testShowFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'is_hidden' => true,
        ]);

        $response = $this->apiRun('GET', "/api/v1/links/{$link->id}");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot access the link.',
        ]);
    }

    public function testShowFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);

        $response = $this->apiRun('GET', "/api/v1/links/{$link->id}");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testUpdateUpdatesTheLink(): void
    {
        $user = $this->login();
        $old_title = 'My link';
        $old_reading_time = 21;
        $new_title = 'The link';
        $new_reading_time = 42;
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/links/{$link->id}", [
            'title' => $new_title,
            'reading_time' => $new_reading_time,
        ]);

        $this->assertResponseCode($response, 200);
        $link = $link->reload();
        $this->assertSame($new_title, $link->title);
        $this->assertSame($new_reading_time, $link->reading_time);
        $this->assertApiResponse($response, $link->toJson($user));
    }

    public function testUpdateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        $old_title = 'My link';
        $old_reading_time = 21;
        $new_title = '';
        $new_reading_time = 42;
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/links/{$link->id}", [
            'title' => $new_title,
            'reading_time' => $new_reading_time,
        ]);

        $this->assertResponseCode($response, 400);
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
        $this->assertSame($old_reading_time, $link->reading_time);
        $this->assertApiError(
            $response,
            'title',
            ['presence', 'The title is required.']
        );
    }

    public function testUpdateFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $old_title = 'My link';
        $old_reading_time = 21;
        $new_title = 'The link';
        $new_reading_time = 42;
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/links/{$link->id}", [
            'title' => $new_title,
            'reading_time' => $new_reading_time,
        ]);

        $this->assertResponseCode($response, 403);
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
        $this->assertSame($old_reading_time, $link->reading_time);
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testUpdateFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $old_title = 'My link';
        $old_reading_time = 21;
        $new_title = 'The link';
        $new_reading_time = 42;
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
        ]);

        $response = $this->apiRun('PATCH', '/api/v1/links/not-an-id', [
            'title' => $new_title,
            'reading_time' => $new_reading_time,
        ]);

        $this->assertResponseCode($response, 404);
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
        $this->assertSame($old_reading_time, $link->reading_time);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testUpdateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $old_title = 'My link';
        $old_reading_time = 21;
        $new_title = 'The link';
        $new_reading_time = 42;
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $old_title,
            'reading_time' => $old_reading_time,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/links/{$link->id}", [
            'title' => $new_title,
            'reading_time' => $new_reading_time,
        ]);

        $this->assertResponseCode($response, 401);
        $link = $link->reload();
        $this->assertSame($old_title, $link->title);
        $this->assertSame($old_reading_time, $link->reading_time);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testDeleteRemovesTheCollection(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}");

        $this->assertResponseCode($response, 200);
        $this->assertFalse(models\Link::exists($link->id));
    }

    public function testDeleteFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot delete the link.',
        ]);
        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testDeleteFailsIfTheCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);

        $response = $this->apiRun('DELETE', '/api/v1/links/not-an-id');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
        $this->assertTrue(models\Link::exists($link->id));
    }

    public function testDeleteFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/links/{$link->id}");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
        $this->assertTrue(models\Link::exists($link->id));
    }
}
