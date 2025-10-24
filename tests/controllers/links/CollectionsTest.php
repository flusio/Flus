<?php

namespace App\controllers\links;

use App\forms;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testIndexRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_3 = CollectionFactory::create([
            'user_id' => $user->id,
            'name' => $collection_name,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseTemplateName($response, 'links/collections/index.phtml');
    }

    public function testIndexRendersCorrectlyWhenMarkAsReadIsSet(): void
    {
        $user = $this->login();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
            'mark_as_read' => '1',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Store the link and mark as read');
    }

    public function testIndexRendersExistingLink(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link_not_owned = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $link_owned = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection_not_owned = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        $collection_owned = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_not_owned->id,
            'collection_id' => $collection_not_owned->id,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link_owned->id,
            'collection_id' => $collection_owned->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link_not_owned->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'links/collections/index.phtml');
        $this->assertResponseContains($response, $link_owned->id);
        $this->assertResponseNotContains($response, $link_not_owned->id);
    }

    public function testIndexRendersCorrectLinkIfDuplicated(): void
    {
        $user = $this->login();
        /** @var string */
        $url = $this->fake('url');
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);

        $response = $this->appRun('GET', "/links/{$link_2->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'links/collections/index.phtml');
        $this->assertResponseContains($response, $link_2->id);
        $this->assertResponseNotContains($response, $link_1->id);
    }

    public function testIndexRendersIfUrlAddedByAnotherUserInCollectionOwned(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $other_link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $other_link->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $other_user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $other_link->id);
    }

    public function testIndexRendersIfUrlAddedByOwnerInCollectionWithWriteAccess(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
        ]);
        $owner_link = LinkFactory::create([
            'user_id' => $owner->id,
            'url' => $url,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $owner_link->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $owner_link->id);
    }

    public function testIndexRendersCollectionSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection->id);
    }

    public function testIndexDoesNotCopyNotOwnedAndAccessibleLinks(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => false,
            'url' => $url,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
            'name' => $collection_name,
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNull($new_link);
    }

    public function testIndexDoesNotRenderCollectionSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection->id);
    }

    public function testIndexRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
    }

    public function testIndexFailsIfLinkIsNotAccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'bookmarks',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections", [
            'from' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesCollectionsAndRedirects(): void
    {
        $user = $this->login();
        /** @var bool */
        $is_hidden = $this->fake('boolean');
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_2->id],
            'is_hidden' => $is_hidden,
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_2->id,
        ]);
        $this->assertNull($link_to_collection_1);
        $this->assertNotNull($link_to_collection_2);
        $link = $link->reload();
        $this->assertSame($is_hidden, $link->is_hidden);
    }

    public function testUpdateDoesNotRemoveFromBookmarksNewsOrReadList(): void
    {
        $user = $this->login();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $read_list = $user->readList();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);
        $link_to_read_list = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
        $this->assertTrue(models\LinkToCollection::exists($link_to_bookmarks->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_news->id));
        $this->assertTrue(models\LinkToCollection::exists($link_to_read_list->id));
    }

    public function testUpdateCreatesNote(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        /** @var string */
        $content = $this->fake('sentence');

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertSame(1, models\Note::count());
        $note = models\Note::take();
        $this->assertNotNull($note);
        $this->assertSame($content, $note->content);
        $this->assertSame($user->id, $note->user_id);
        $this->assertSame($link->id, $note->link_id);
    }

    public function testUpdateChangesTags(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'tags' => [],
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $content = '#foo #Bar';

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
            'content' => $content,
        ]);

        $link = $link->reload();
        $this->assertEquals(['foo' => 'foo', 'bar' => 'Bar'], $link->tags);
    }

    public function testUpdateCanMarkAsRead(): void
    {
        $user = $this->login();
        $read_list = $user->readList();
        $bookmarks = $user->bookmarks();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $link_to_bookmarks = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $bookmarks->id,
        ]);
        $link_to_news = LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $news->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
            'mark_as_read' => '1',
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $exists_in_bookmarks = models\LinkToCollection::exists($link_to_bookmarks->id);
        $exists_in_news = models\LinkToCollection::exists($link_to_news->id);
        $link_to_read_list = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $read_list->id,
        ]);
        $this->assertFalse($exists_in_bookmarks);
        $this->assertFalse($exists_in_news);
        $this->assertNotNull($link_to_read_list);
    }

    public function testUpdateCopiesNotOwnedAndAccessibleLinks(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $url = $this->fake('url');
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'url' => $url,
            'is_hidden' => false,
        ]);
        $other_collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $owned_collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $other_collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $other_collection->id]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => $from,
            'collection_ids' => [$owned_collection->id],
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link didn't change of collections
        $link_to_other_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $other_collection->id,
        ]);
        $link_to_owned_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $owned_collection->id,
        ]);
        $this->assertNotNull($link_to_other_collection);
        $this->assertNull($link_to_owned_collection);
        // But a new link exists, attached to the owned collection
        $new_link = models\Link::findBy([
            'user_id' => $user->id,
            'url' => $url,
        ]);
        $this->assertNotNull($new_link);
        $this->assertSame('collection', $new_link->source_type);
        $this->assertSame($other_collection->id, $new_link->source_resource_id);
        $new_link_to_other_collection = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $other_collection->id,
        ]);
        $new_link_to_owned_collection = models\LinkToCollection::findBy([
            'link_id' => $new_link->id,
            'collection_id' => $owned_collection->id,
        ]);
        $this->assertNull($new_link_to_other_collection);
        $this->assertNotNull($new_link_to_owned_collection);
    }

    public function testUpdateWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $link_to_collection = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection->id,
        ]);
        $this->assertNotNull($link_to_collection);
    }

    public function testUpdateCanCreateCollections(): void
    {
        $user = $this->login();
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, models\Collection::count());

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(1, models\Collection::count());

        $link = $link->reload();
        $collection = models\Collection::findBy([
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertNotNull($collection);
        $this->assertContains($collection->id, array_column($link->collections(), 'id'));
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_2->id],
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fbookmarks');
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_2->id,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfLinkIsNotAccessible(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $collection_1 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'bookmarks',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        LinkToCollectionFactory::create([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection_2->id],
        ]);

        $this->assertResponseCode($response, 404);
        $link_to_collection_1 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_1->id,
        ]);
        $link_to_collection_2 = models\LinkToCollection::findBy([
            'link_id' => $link->id,
            'collection_id' => $collection_2->id,
        ]);
        $this->assertNotNull($link_to_collection_1);
        $this->assertNull($link_to_collection_2);
    }

    public function testUpdateFailsIfCollectionIdsContainsNotOwnedId(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfNewCollectionNameIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $collection_name = $this->fake('words', 100, true);
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'from' => \Minz\Url::for('bookmarks'),
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(0, models\Collection::count());

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters.');
        $this->assertSame(0, models\LinkToCollection::count());
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => 'not the token',
            'from' => \Minz\Url::for('bookmarks'),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\LinkToCollection::count());
    }
}
