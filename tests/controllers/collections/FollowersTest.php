<?php

namespace App\controllers\collections;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class FollowersTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
            'name' => $collection_name,
        ]);
        $user->follow($collection->id);
        /** @var string */
        $stream_name = $this->fake('text', 50);
        StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/followers/edit.html.twig');
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseContains($response, $stream_name);
    }

    public function testEditSuggestsATimeFilterBasedOnThePublicationRate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
            // At least one link per day.
            'publication_frequency_per_year' => 400,
        ]);
        $user->follow($collection->id);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains(
            $response,
            'The links published during the last 24 hours (recommended)'
        );
    }

    public function testEditSuggestsToAddTheSourceToAStreamIfItPublishesTooMuch(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
            // At least five links per day.
            'publication_frequency_per_year' => 2000,
        ]);
        $user->follow($collection->id);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'No link (recommended)');
        $this->assertResponseContains($response, 'add it to a stream to keep up with it');
    }

    public function testEditDisplaysADedicatedTextIfSourceIsInactive(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
            'publication_frequency_per_year' => 0,
        ]);
        $user->follow($collection->id);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'This source published nothing over the past year');
    }

    public function testEditFailsIfCollectionIsNotFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsPrivate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $user->follow($collection->id);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $redirect_to = urlencode("/collections/{$collection->id}/follow/edit");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testUpdateChangesTheTimeFilter(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $followed_collection = $user->followedCollection($collection->id);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $followed_collection = $followed_collection->reload();
        $this->assertSame('strict', $followed_collection->time_filter);
    }

    public function testUpdateAttachesTheSelectedStreams(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
            'stream_ids' => [$stream->id],
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $this->assertTrue($stream->hasSource($collection));
    }

    public function testUpdateDetachesTheUnselectedStreams(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->addSource($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $this->assertFalse($stream->hasSource($collection));
    }

    public function testUpdateAttachesAndDetachesTheStreamsAtOnce(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $kept_stream = StreamFactory::create(['user_id' => $user->id]);
        $detached_stream = StreamFactory::create(['user_id' => $user->id]);
        $attached_stream = StreamFactory::create(['user_id' => $user->id]);
        $kept_stream->addSource($collection);
        $detached_stream->addSource($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
            'stream_ids' => [$kept_stream->id, $attached_stream->id],
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $this->assertTrue($kept_stream->hasSource($collection));
        $this->assertTrue($attached_stream->hasSource($collection));
        $this->assertFalse($detached_stream->hasSource($collection));
    }

    public function testUpdateCreatesTheNewStream(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
            'new_stream_name' => 'My stream',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $stream = models\Stream::findBy(['user_id' => $user->id]);
        $this->assertNotNull($stream);
        $this->assertSame('My stream', $stream->name);
        $this->assertTrue($stream->hasSource($collection));
    }

    public function testUpdateReusesTheStreamWithTheSameName(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'My stream',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
            'new_stream_name' => 'My stream',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $this->assertSame(1, models\Stream::count());
        $this->assertTrue($stream->hasSource($collection));
    }

    public function testUpdateAcceptsAStreamBothSelectedAndNamed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'My stream',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
            'stream_ids' => [$stream->id],
            'new_stream_name' => 'My stream',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $this->assertSame(1, models\Stream::count());
        $this->assertTrue($stream->hasSource($collection));
    }

    public function testUpdateDoesNotCreateAStreamIfNameIsEmpty(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
            'new_stream_name' => '',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $this->assertSame(0, models\Stream::count());
    }

    public function testUpdateFailsIfNewStreamNameIsTooLong(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $followed_collection = $user->followedCollection($collection->id);
        $stream_name = str_repeat('a', models\Stream::NAME_MAX_LENGTH + 1);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'strict',
            'new_stream_name' => $stream_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters.');
        $this->assertSame(0, models\Stream::count());
        $followed_collection = $followed_collection->reload();
        $this->assertSame('normal', $followed_collection->time_filter);
    }

    public function testUpdateFailsIfStreamIsOwnedByAnotherUser(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'normal',
            'stream_ids' => [$stream->id],
        ]);
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the selected streams doesn’t exist.');
        $this->assertFalse($stream->hasSource($collection));
    }

    public function testUpdateFailsIfTimeFilterIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $followed_collection = $user->followedCollection($collection->id);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollow::class),
            'time_filter' => 'invalid',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The filter is invalid');
        $followed_collection = $followed_collection->reload();
        $this->assertSame('normal', $followed_collection->time_filter);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection->id);
        $followed_collection = $user->followedCollection($collection->id);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => 'not the token',
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $followed_collection = $followed_collection->reload();
        $this->assertSame('normal', $followed_collection->time_filter);
    }

    public function testCreateMakesUserFollowingAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $this->assertSame(0, models\FollowedCollection::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\collections\FollowCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(1, models\FollowedCollection::count());
        $followed_collection = models\FollowedCollection::take();
        $this->assertNotNull($followed_collection);
        $this->assertSame($user->id, $followed_collection->user_id);
        $this->assertSame($collection->id, $followed_collection->collection_id);
    }

    public function testCreateWorksIfCollectionIsShared(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\collections\FollowCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(1, models\FollowedCollection::count());
        $followed_collection = models\FollowedCollection::take();
        $this->assertNotNull($followed_collection);
        $this->assertSame($user->id, $followed_collection->user_id);
        $this->assertSame($collection->id, $followed_collection->collection_id);
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\collections\FollowCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testCreateFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', '/collections/unknown/follow', [
            'csrf_token' => $this->csrfToken(forms\collections\FollowCollection::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testCreateFailsIfUserHasNoAccess(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => false,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\collections\FollowCollection::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testDeleteMakesUserUnfollowingAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\collections\UnfollowCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testDeleteWorksIfCollectionIsShared(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\collections\UnfollowCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testDeleteWorksIfUserHasNoAccessToTheCollection(): void
    {
        // This can happen if a user follow a collection, but its owner change
        // the visibility.
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => false,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\collections\UnfollowCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\collections\UnfollowCollection::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertSame(1, models\FollowedCollection::count());
    }

    public function testDeleteFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', '/collections/unknown/unfollow', [
            'csrf_token' => $this->csrfToken(forms\collections\UnfollowCollection::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(1, models\FollowedCollection::count());
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertSame(1, models\FollowedCollection::count());
    }
}
