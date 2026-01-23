<?php

namespace App\controllers;

use App\forms;
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
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

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
        $this->assertResponseTemplateName($response, 'collections/new.html.twig');
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
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
            'csrf_token' => 'not the token',
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
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
        $this->assertResponseTemplateName($response, 'collections/show.html.twig');
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
        $this->assertResponseTemplateName($response, 'collections/show.html.twig');
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
        $this->assertResponseTemplateName($response, 'collections/show.html.twig');
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
        $this->assertResponseTemplateName($response, 'collections/show.html.twig');
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
        $this->assertResponseTemplateName($response, 'collections/show.html.twig');
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

    public function testShowFailsIfPageIsOutOfBound(): void
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

        $this->assertResponseCode($response, 404);
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

        $this->assertResponseCode($response, 403);
    }

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/edit.html.twig');
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

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/edit.html.twig');
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Fedit");
    }

    public function testEditFailsIfCollectionDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/collections/unknown/edit');

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

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit");

        $this->assertResponseCode($response, 403);
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

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testEditFailsIfCollectionIsNotOfCorrectType(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/edit");

        $this->assertResponseCode($response, 403);
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/edit");
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => [$new_topic->id],
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/edit");
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Fedit");
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => 'not the token',
            'name' => $new_name,
            'description' => $new_description,
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
            'is_public' => $new_public,
            'topic_ids' => ['not an id'],
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
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponseCode($response, 403);
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
        ]);

        $this->assertResponseCode($response, 403);
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

        $response = $this->appRun('POST', "/collections/{$collection->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\Collection::class),
            'name' => $new_name,
            'description' => $new_description,
        ]);

        $this->assertResponseCode($response, 403);
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
            'csrf_token' => $this->csrfToken(forms\collections\DeleteCollection::class),
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
            'csrf_token' => $this->csrfToken(forms\collections\DeleteCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
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
            'csrf_token' => $this->csrfToken(forms\collections\DeleteCollection::class),
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
            'csrf_token' => $this->csrfToken(forms\collections\DeleteCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
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
            'csrf_token' => $this->csrfToken(forms\collections\DeleteCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
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
            'csrf_token' => $this->csrfToken(forms\collections\DeleteCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
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
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue(models\Collection::exists($collection->id));
        $error = \Minz\Flash::get('error');
        $this->assertTrue(is_string($error));
        $this->assertStringContainsString('A security verification failed', $error);
    }
}
