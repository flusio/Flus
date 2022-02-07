<?php

namespace flusio\controllers;

use flusio\models;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection_name_1);
        $this->assertResponseContains($response, $collection_name_2);
        $this->assertResponsePointer($response, 'collections/index.phtml');
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

        $this->assertResponseContains($response, $collection_name);
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

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/collections');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fcollections');
    }

    public function testNewRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/collections/new');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'New collection');
        $this->assertResponsePointer($response, 'collections/new.phtml');
    }

    public function testNewRendersTopics()
    {
        $user = $this->login();
        $label = $this->fake('word');
        $this->create('topic', [
            'label' => $label,
        ]);

        $response = $this->appRun('get', '/collections/new');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $label);
    }

    public function testNewRedirectsIfNotConnected()
    {
        $response = $this->appRun('get', '/collections/new');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
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
        $this->assertResponseCode($response, 302, "/collections/{$collection->id}");
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
            'csrf' => \Minz\CSRF::generate(),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
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

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
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

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters');
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

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
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

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated topic doesn’t exist.');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/show.phtml');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/show_public.phtml');
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

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/show_public.phtml');
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

        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowRedirectsIfPageIsOutOfBound()
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

        $response = $this->appRun('get', "/collections/{$collection_id}", [
            'page' => 0,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection_id}?page=1");
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

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}");
    }

    public function testShowFailsIfCollectionDoesNotExist()
    {
        $this->login();

        $response = $this->appRun('get', '/collections/unknown');

        $this->assertResponseCode($response, 404);
    }

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/edit.phtml');
    }

    public function testEditRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testEditFailsIfCollectionDoesNotExist()
    {
        $this->login();

        $response = $this->appRun('get', '/collections/unknown/edit', [
            'from' => \Minz\Url::for('collections'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotOwnedByCurrentUser()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotOfCorrectType()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => [$new_topic_id],
            'from' => $from,
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => \Minz\CSRF::generate(),
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => 'not the token',
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters');
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => ['not an id'],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated topic doesn’t exist.');
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
            'from' => \Minz\Url::for('collections'),
        ]);

        $this->assertResponseCode($response, 404);
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
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
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
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

        $this->assertResponseCode($response, 302, '/collections');
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
            'csrf' => \Minz\CSRF::generate(),
            'from' => "/collections/{$collection_id}/edit",
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection_id}%2Fedit");
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

        $this->assertResponseCode($response, 404);
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

        $this->assertResponseCode($response, 404);
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

        $this->assertResponseCode($response, 404);
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

        $this->assertResponseCode($response, 302, "/collections/{$collection_id}/edit");
        $this->assertTrue(models\Collection::exists($collection_id));
        $this->assertFlash('error', 'A security verification failed.');
    }
}
