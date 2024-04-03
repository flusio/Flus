<?php

namespace App\controllers;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\CollectionToTopicFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\TopicFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRedirectsToLinks(): void
    {
        $response = $this->appRun('GET', '/collections');

        $this->assertResponseCode($response, 301, '/links');
    }

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/collections/new');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'New collection');
        $this->assertResponsePointer($response, 'collections/new.phtml');
    }

    public function testNewRendersTopics(): void
    {
        $user = $this->login();
        /** @var string */
        $label = $this->fake('word');
        TopicFactory::create([
            'label' => $label,
        ]);

        $response = $this->appRun('GET', '/collections/new');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $label);
    }

    public function testNewRedirectsIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/collections/new');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
    }

    public function testCreateCreatesCollectionAndRedirects(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertSame(1, models\Collection::count());
        $collection = models\Collection::take();
        $this->assertNotNull($collection);
        $this->assertResponseCode($response, 302, "/collections/{$collection->id}");
        $this->assertSame($name, $collection->name);
        $this->assertSame($description, $collection->description);
        $this->assertFalse($collection->is_public);
    }

    public function testCreateAllowsToCreatePublicCollections(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
            'is_public' => true,
        ]);

        $collection = models\Collection::take();
        $this->assertNotNull($collection);
        $this->assertTrue($collection->is_public);
    }

    public function testCreateAllowsToAttachTopics(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');
        $topic = TopicFactory::create();

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
            'topic_ids' => [$topic->id],
        ]);

        $collection = models\Collection::take();
        $this->assertNotNull($collection);
        $this->assertContains($topic->id, array_column($collection->topics(), 'id'));
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => \Minz\Csrf::generate(),
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fcollections%2Fnew');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => 'not the token',
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfNameIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 100, true);
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        /** @var string */
        $description = $this->fake('sentence');

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => $user->csrf,
            'description' => $description,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
        $this->assertSame(0, models\Collection::count());
    }

    public function testCreateFailsIfTopicIdsIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $name = $this->fake('words', 3, true);
        /** @var string */
        $description = $this->fake('sentence');
        $topic = TopicFactory::create();

        $response = $this->appRun('POST', '/collections/new', [
            'csrf' => $user->csrf,
            'name' => $name,
            'description' => $description,
            'topic_ids' => ['not an id'],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated topic doesn’t exist.');
        $this->assertSame(0, models\Collection::count());
    }

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'description' => '**foo bar**',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseContains($response, '<strong>foo bar</strong>');
        $this->assertResponsePointer($response, 'collections/show.phtml');
    }

    public function testShowRendersCorrectlyIfPublicAndNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/show_public.phtml');
    }

    public function testShowRendersCorrectlyIfPublicAndDoesNotOwnTheLink(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $owner->id,
            'title' => $link_title,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/show_public.phtml');
    }

    public function testShowRendersCorrectlyIfCollectionIsPrivateAndSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/show_public.phtml');
    }

    public function testShowRendersCorrectlyIfCollectionIsPrivateAndSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/show.phtml');
    }

    public function testShowHidesHiddenLinksInPublicCollections(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowRedirectsIfPageIsOutOfBound(): void
    {
        $user = $this->login();
        /** @var string */
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}", [
            'page' => 0,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}?page=1");
    }

    public function testShowRedirectsIfPrivateAndNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}");
    }

    public function testShowFailsIfCollectionDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/collections/unknown');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfPrivateAndNoSharedAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}");

        $this->assertResponseCode($response, 404);
    }

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/edit.phtml');
    }

    public function testEditRendersCorrectlyIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/edit.phtml');
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testEditFailsIfCollectionDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/collections/unknown/edit', [
            'from' => \Minz\Url::for('collections'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotOfCorrectType(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateUpdatesCollectionAndRedirects(): void
    {
        $user = $this->login();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $old_description = $this->fakeUnique('sentence');
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $old_public = false;
        $new_public = true;
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
            'is_public' => $old_public,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $collection = $collection->reload();
        $this->assertSame($new_name, $collection->name);
        $this->assertSame($new_description, $collection->description);
        $this->assertTrue($collection->is_public);
    }

    public function testUpdateChangesTopics(): void
    {
        $user = $this->login();
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $new_public = 1;
        $old_topic = TopicFactory::create();
        $new_topic = TopicFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $old_topic->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => [$new_topic->id],
            'from' => $from,
        ]);

        $collection = $collection->reload();
        $topic_ids = array_column($collection->topics(), 'id');
        $this->assertSame([$new_topic->id], $topic_ids);
    }

    public function testUpdateWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'name' => $old_name,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_name,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $collection = $collection->reload();
        $this->assertSame($new_name, $collection->name);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $old_description = $this->fakeUnique('sentence');
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => \Minz\Csrf::generate(),
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $old_description = $this->fakeUnique('sentence');
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => 'not the token',
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfNameIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 100, true);
        /** @var string */
        $old_description = $this->fakeUnique('sentence');
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters');
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        $new_name = '';
        /** @var string */
        $old_description = $this->fakeUnique('sentence');
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfTopicIdsIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $new_public = 1;
        $old_topic = TopicFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        CollectionToTopicFactory::create([
            'collection_id' => $collection->id,
            'topic_id' => $old_topic->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => ['not an id'],
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated topic doesn’t exist.');
        $collection = $collection->reload();
        $topic_ids = array_column($collection->topics(), 'id');
        $this->assertSame([$old_topic->id], $topic_ids);
    }

    public function testUpdateFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_description = $this->fakeUnique('sentence');

        $response = $this->appRun('POST', '/collections/unknown/edit', [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => \Minz\Url::for('collections'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $old_description = $this->fakeUnique('sentence');
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'name' => $old_name,
            'description' => $old_description,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testUpdateFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'name' => $old_name,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $new_name,
        ]);

        $this->assertResponseCode($response, 404);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
    }

    public function testUpdateFailsIfCollectionIsNotOfCorrectType(): void
    {
        $user = $this->login();
        /** @var string */
        $old_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $new_name = $this->fakeUnique('words', 3, true);
        /** @var string */
        $old_description = $this->fakeUnique('sentence');
        /** @var string */
        $new_description = $this->fakeUnique('sentence');
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
            'name' => $old_name,
            'description' => $old_description,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf' => $user->csrf,
            'name' => $new_name,
            'description' => $new_description,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $collection = $collection->reload();
        $this->assertSame($old_name, $collection->name);
        $this->assertSame($old_description, $collection->description);
    }

    public function testDeleteDeletesCollectionAndRedirects(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection->id}/edit",
        ]);

        $this->assertResponseCode($response, 302, '/links');
        $this->assertFalse(models\Collection::exists($collection->id));
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/delete", [
            'csrf' => \Minz\Csrf::generate(),
            'from' => "/collections/{$collection->id}/edit",
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Fedit");
        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', '/collections/unknown/delete', [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection->id}/edit",
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection->id}/edit",
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfCollectionIsShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection->id}/edit",
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfCollectionIsNotOfCorrectType(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/delete", [
            'csrf' => $user->csrf,
            'from' => "/collections/{$collection->id}/edit",
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Collection::exists($collection->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/delete", [
            'csrf' => 'not the token',
            'from' => "/collections/{$collection->id}/edit",
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/edit");
        $this->assertTrue(models\Collection::exists($collection->id));
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
    }
}
