<?php

namespace flusio;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
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

    public function testIndexRendersFollowedCollections()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/collections');

        $response_output = $response->render();
        $this->assertStringContainsString($collection_name, $response_output);
    }

    public function testIndexDoesNotRenderFollowedCollectionsIfNotPublic()
    {
        // This can happen if a user switch the visibility of its collection
        // back to "private"
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', '/collections');

        $response_output = $response->render();
        $this->assertStringNotContainsString($collection_name, $response_output);
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

    public function testNewRendersTopics()
    {
        $user = $this->login();
        $label = $this->fake('word');
        $this->create('topic', [
            'label' => $label,
        ]);

        $response = $this->appRun('get', '/collections/new');

        $this->assertResponse($response, 200, $label);
    }

    public function testNewRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/collections/new');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
    }

    public function testCreateCreatesCollectionAndRedirects()
    {
        $user = $this->login();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertSame(1, models\Collection::count());
        $collection = models\Collection::take();
        $this->assertResponse($response, 302, "/collections/{$collection->id}");
        $this->assertSame($name, $collection->name);
        $this->assertSame($description, $collection->description);
        $this->assertFalse($collection->is_public);
    }

    public function testCreateAllowsToCreatePublicCollections()
    {
        $user = $this->login();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
            'is_public' => true,
        ]);

        $collection = models\Collection::take();
        $this->assertTrue($collection->is_public);
    }

    public function testCreateAllowsToAttachTopics()
    {
        $user = $this->login();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');
        $topic_id = $this->create('topic');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
            'topic_ids' => [$topic_id],
        ]);

        $collection = models\Collection::take();
        $this->assertContains($topic_id, array_column($collection->topics(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => 'not the token',
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfNameIsInvalid()
    {
        $user = $this->login();
        $name = $this->fake('words', 100, true);
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponse($response, 400, 'The name must be less than 100 characters');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfNameIsMissing()
    {
        $user = $this->login();
        $description = $this->fake('sentence');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => $user->csrf,
            'description' => $description,
        ]);

        $this->assertResponse($response, 400, 'The name is required');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfTopicIdsIsInvalid()
    {
        $user = $this->login();
        $name = $this->fake('words', 3, true);
        $description = $this->fake('sentence');
        $topic_id = $this->create('topic');

        $response = $this->appRun('post', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
            'topic_ids' => ['not an id'],
        ]);

        $this->assertResponse($response, 400, 'One of the associated topic doesn’t exist.');
        $this->assertSame(0, models\Collection::count());
    }

    public function testShowRendersCorrectly()
    {
        $user = $this->login();
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => 0,
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

    public function testShowRendersCorrectlyIfPublicAndNotConnected()
    {
        $user_id = $this->create('user');
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $link_title,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}");

        $this->assertResponse($response, 200, $link_title);
        $this->assertPointer($response, 'collections/show_public.phtml');
    }

    public function testShowRendersCorrectlyIfPublicAndDoesNotOwnTheLink()
    {
        $user = $this->login();
        $owner_id = $this->create('user');
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $owner_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $owner_id,
            'title' => $link_title,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}");

        $this->assertResponse($response, 200, $link_title);
        $this->assertPointer($response, 'collections/show_public.phtml');
    }

    public function testShowHidesHiddenLinksInPublicCollections()
    {
        $user_id = $this->create('user');
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'title' => $link_title,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}");

        $this->assertStringNotContainsString($link_title, $response->render());
    }

    public function testShowRedirectsIfPrivateAndNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 0,
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

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit");

        $this->assertResponse($response, 200);
        $this->assertPointer($response, 'collections/edit.phtml');
    }

    public function testEditRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit");

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}%2Fedit");
    }

    public function testEditFailsIfCollectionDoesNotExist()
    {
        $this->login();

        $response = $this->appRun('get', '/collections/unknown/edit');

        $this->assertResponse($response, 404);
    }

    public function testEditFailsIfCollectionIsNotOwnedByCurrentUser()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit");

        $this->assertResponse($response, 404);
    }

    public function testEditFailsIfCollectionIsNotOfCorrectType()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit");

        $this->assertResponse($response, 404);
    }

    public function testUpdateUpdatesCollectionAndRedirects()
    {
        $user = $this->login();
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = $this->fakeUnique('words', 3, true);
        $old_description = $this->fakeUnique('sentence');
        $new_description = $this->fakeUnique('sentence');
        $old_public = 0;
        $new_public = 1;
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_public,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
        ]);

        $this->assertResponse($response, 302, "/collections/{$collection_id}");
        $collection = models\Collection::take();
        $this->assertSame($new_name, $collection->name);
        $this->assertSame($new_description, $collection->description);
        $this->assertTrue($collection->is_public);
    }

    public function testUpdateChangesTopics()
    {
        $user = $this->login();
        $new_name = $this->fakeUnique('words', 3, true);
        $new_description = $this->fakeUnique('sentence');
        $new_public = 1;
        $old_topic_id = $this->create('topic');
        $new_topic_id = $this->create('topic');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $old_topic_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => [$new_topic_id],
        ]);

        $collection = models\Collection::take();
        $topic_ids = array_column($collection->topics(), 'id');
        $this->assertSame([$new_topic_id], $topic_ids);
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = $this->fakeUnique('words', 3, true);
        $old_description = $this->fakeUnique('sentence');
        $new_description = $this->fakeUnique('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}%2Fedit");
        $collection = models\Collection::take();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = $this->fakeUnique('words', 3, true);
        $old_description = $this->fakeUnique('sentence');
        $new_description = $this->fakeUnique('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => 'not the token',
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $collection = models\Collection::take();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfNameIsInvalid()
    {
        $user = $this->login();
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = $this->fakeUnique('words', 100, true);
        $old_description = $this->fakeUnique('sentence');
        $new_description = $this->fakeUnique('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponse($response, 400, 'The name must be less than 100 characters');
        $collection = models\Collection::take();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfNameIsMissing()
    {
        $user = $this->login();
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = '';
        $old_description = $this->fakeUnique('sentence');
        $new_description = $this->fakeUnique('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponse($response, 400, 'The name is required');
        $collection = models\Collection::take();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfTopicIdsIsInvalid()
    {
        $user = $this->login();
        $new_name = $this->fakeUnique('words', 3, true);
        $new_description = $this->fakeUnique('sentence');
        $new_public = 1;
        $old_topic_id = $this->create('topic');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $this->create('collection_to_topic', [
            'collection_id' => $collection_id,
            'topic_id' => $old_topic_id,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => ['not an id'],
        ]);

        $this->assertResponse($response, 400, 'One of the associated topic doesn’t exist.');
        $collection = models\Collection::find($collection_id);
        $topic_ids = array_column($collection->topics(), 'id');
        $this->assertSame([$old_topic_id], $topic_ids);
    }

    public function testUpdateFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $new_name = $this->fakeUnique('words', 3, true);
        $new_description = $this->fakeUnique('sentence');

        $response = $this->appRun('post', '/collections/unknown/edit', [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponse($response, 404);
    }

    public function testUpdateFailsIfCollectionIsNotOwnedByCurrentUser()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = $this->fakeUnique('words', 3, true);
        $old_description = $this->fakeUnique('sentence');
        $new_description = $this->fakeUnique('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponse($response, 404);
        $collection = models\Collection::take();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfCollectionIsNotOfCorrectType()
    {
        $user = $this->login();
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = $this->fakeUnique('words', 3, true);
        $old_description = $this->fakeUnique('sentence');
        $new_description = $this->fakeUnique('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
            'name' => $old_name,
            'description' => $old_description,
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponse($response, 404);
        $collection = models\Collection::take();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testDeleteDeletesCollectionAndRedirects()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection_id}/edit",
        ]);

        $this->assertResponse($response, 302, '/collections');
        $this->assertFalse(models\Collection::exists($collection_id));
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/delete", [
            'csrf' => (new \Minz\CSRF())->generateToken(),
            'from' => "/collections/{$collection_id}/edit",
        ]);

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}%2Fedit");
        $this->assertTrue(models\Collection::exists($collection_id));
    }

    public function testDeleteFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', '/collections/unknown/delete', [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection_id}/edit",
        ]);

        $this->assertResponse($response, 404);
        $this->assertTrue(models\Collection::exists($collection_id));
    }

    public function testDeleteFailsIfCollectionIsNotOwnedByCurrentUser()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection_id}/edit",
        ]);

        $this->assertResponse($response, 404);
        $this->assertTrue(models\Collection::exists($collection_id));
    }

    public function testDeleteFailsIfCollectionIsNotOfCorrectType()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection_id}/edit",
        ]);

        $this->assertResponse($response, 404);
        $this->assertTrue(models\Collection::exists($collection_id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('post', "/collections/{$collection_id}/delete", [
            'csrf' => 'not the token',
            'from' => "/collections/{$collection_id}/edit",
        ]);

        $this->assertResponse($response, 302, "/collections/{$collection_id}/edit");
        $this->assertTrue(models\Collection::exists($collection_id));
        $this->assertFlash('error', 'A security verification failed.');
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

    public function testDiscoverRendersCorrectly()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $this->assertResponse($response, 200, $collection_name);
        $this->assertPointer($response, 'collections/discover.phtml');
    }

    public function testDiscoverDoesNotListOwnedCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringNotContainsString($collection_name, $output);
    }

    public function testDiscoverDoesNotListEmptyCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringNotContainsString($collection_name, $output);
    }

    public function testDiscoverDoesNotListPrivateCollections()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 0,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringNotContainsString($collection_name, $output);
    }

    public function testDiscoverDoesNotCountHiddenLinks()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id1 = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $link_id2 = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id2,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $output = $response->render();
        $this->assertStringContainsString('1 link', $output);
        $this->assertStringNotContainsString('2 links', $output);
    }

    public function testDiscoverRedirectsIfPageIsOutOfBound()
    {
        $user = $this->login();
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover', [
            'page' => 0,
        ]);

        $this->assertResponse($response, 302, '/collections/discover?page=1');
    }

    public function testDiscoverRedirectsIfNotConnected()
    {
        $collection_name = $this->fake('sentence');
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', '/collections/discover');

        $this->assertResponse($response, 302, "/login?redirect_to=%2Fcollections%2Fdiscover");
    }

    public function testFollowMakesUserFollowingAndRedirects()
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

    public function testFollowRedirectsIfNotConnected()
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

    public function testFollowFailsIfCollectionDoesNotExist()
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

    public function testFollowFailsIfUserHasNoAccess()
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

    public function testFollowFailsIfCsrfIsInvalid()
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

    public function testUnfollowMakesUserUnfollowingAndRedirects()
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

    public function testUnfollowRedirectsIfNotConnected()
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

    public function testUnfollowFailsIfCollectionDoesNotExist()
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

    public function testUnfollowFailsIfUserHasNoAccess()
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

    public function testUnfollowFailsIfCsrfIsInvalid()
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
