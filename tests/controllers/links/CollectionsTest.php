<?php

namespace App\controllers\links;

use App\forms;
use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
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
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'name' => $collection_name,
            'type' => 'collection',
        ]);
        $collection_1->addLinks([$link]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseTemplateName($response, 'links/collections/index.html.twig');
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
        $collection_not_owned->addLinks([$link_not_owned]);
        $collection_owned->addLinks([$link_owned]);

        $response = $this->appRun('GET', "/links/{$link_not_owned->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'links/collections/index.html.twig');
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

        $response = $this->appRun('GET', "/links/{$link_2->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'links/collections/index.html.twig');
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
        $collection->addLinks([$other_link]);
        CollectionShareFactory::create([
            'user_id' => $other_user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

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
        $collection->addLinks([$owner_link]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

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

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

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
        $collection->addLinks([$link]);

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

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

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

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

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fcollections");
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

        $response = $this->appRun('GET', "/links/{$link->id}/collections");

        $this->assertResponseCode($response, 403);
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
        $collection_1->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'collection_ids' => [$collection_2->id],
            'is_hidden' => $is_hidden,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/collections");
        $this->assertFalse($collection_1->hasLink($link));
        $this->assertTrue($collection_2->hasLink($link));
        $link = $link->reload();
        $this->assertSame($is_hidden, $link->is_hidden);
    }

    public function testUpdateDoesNotRemoveFromNews(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $news->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/collections");
        $this->assertTrue($collection->hasLink($link));
        $this->assertTrue($news->hasLink($link));
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
            'collection_ids' => [$collection->id],
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/collections");
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
            'collection_ids' => [$collection->id],
            'content' => $content,
        ]);

        $link = $link->reload();
        $this->assertEquals(['foo' => 'foo', 'bar' => 'Bar'], $link->tags);
    }

    public function testUpdateCanMarkAsRead(): void
    {
        $user = $this->login();
        $news = $user->news();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $user->markAsReadLater($link);
        $collection->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'collection_ids' => [$collection->id],
            'mark_as_read' => '1',
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/collections");
        $this->assertTrue($user->hasRead($link));
        $this->assertFalse($user->hasReadLater($link));
        $this->assertFalse($news->hasLink($link));
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
        $other_collection->addLinks([$link]);
        $from = \Minz\Url::for('collection', ['id' => $other_collection->id]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'collection_ids' => [$owned_collection->id],
        ], headers: [
            'Referer' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        // The initial link didn't change collections
        $this->assertTrue($other_collection->hasLink($link));
        $this->assertFalse($owned_collection->hasLink($link));
        // But a new link exists, attached to the owned collection
        $collection_links = $owned_collection->links();
        $this->assertSame(1, count($collection_links));
        $new_link = $collection_links[0];
        $this->assertSame($user->id, $new_link->user_id);
        $this->assertSame($url, $new_link->url);
        $origin = \Minz\Url::absoluteFor('collection', ['id' => $other_collection->id]);
        $this->assertSame($origin, $new_link->origin);
        $this->assertFalse($other_collection->hasLink($new_link));
        $this->assertTrue($owned_collection->hasLink($new_link));
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
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/collections");
        $this->assertTrue($collection->hasLink($link));
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
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(1, models\Collection::count());

        $link = $link->reload();
        $collection = models\Collection::findBy([
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $this->assertResponseCode($response, 302, "/links/{$link->id}/collections");
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
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
        ]);
        $collection_1->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'collection_ids' => [$collection_2->id],
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fcollections");
        $this->assertTrue($collection_1->hasLink($link));
        $this->assertFalse($collection_2->hasLink($link));
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
            'type' => 'collection',
        ]);
        $collection_2 = CollectionFactory::create([
            'user_id' => $other_user->id,
            'type' => 'collection',
        ]);
        $collection_1->addLinks([$link]);

        $response = $this->appRun('POST', "/links/{$link->id}/collections", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkCollections::class),
            'collection_ids' => [$collection_2->id],
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue($collection_1->hasLink($link));
        $this->assertFalse($collection_2->hasLink($link));
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
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertFalse($collection->hasLink($link));
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
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertFalse($collection->hasLink($link));
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
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the associated collection doesn’t exist.');
        $this->assertFalse($collection->hasLink($link));
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
            'new_collection_names' => [$collection_name],
        ]);

        $this->assertSame(0, models\Collection::count());

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters.');
        $this->assertSame(0, models\Collection::count());
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
            'collection_ids' => [$collection->id],
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertFalse($collection->hasLink($link));
    }
}
