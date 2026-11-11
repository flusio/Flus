<?php

namespace App\controllers\sources;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FeedFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class FollowsTest extends \PHPUnit\Framework\TestCase
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
            'user_id' => $other_user->id,
            'is_public' => true,
            'name' => $collection_name,
        ]);
        $user->follow($collection);
        /** @var string */
        $stream_name = $this->fake('text', 50);
        StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'sources/follows/edit.html.twig');
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseContains($response, $stream_name);
        $this->assertResponseContains($response, 'Custom name');
    }

    public function testEditHidesTheCustomNameIfTheUserOwnsTheSource(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, 'Custom name');
    }

    public function testEditSuggestsATimeFilterBasedOnThePublicationRate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
            // At least one link per day.
            'publication_frequency_per_year' => 400,
        ]);
        $user->follow($collection);

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
            'user_id' => $other_user->id,
            'is_public' => true,
            // At least three links per day.
            'publication_frequency_per_year' => 3 * 365,
        ]);
        $user->follow($collection);

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
            'user_id' => $other_user->id,
            'is_public' => true,
            'publication_frequency_per_year' => 0,
        ]);
        $user->follow($collection);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'This source published nothing over the past year');
    }

    public function testEditFailsIfSourceIsNotFollowed(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfSourceIsPrivate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $user->follow($collection);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $follow = $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/edit");
        $follow = $follow->reload();
        $this->assertSame('strict', $follow->time_filter);
    }

    public function testUpdateRenamesTheSource(): void
    {
        $user = $this->login();
        $feed = FeedFactory::create([
            'name' => 'Carnet de Flus',
            'is_public' => true,
        ]);
        $user->follow($feed);

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
            'name' => 'Flus news',
            'time_filter' => 'normal',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$feed->id}/follow/edit");
        $user = $user->reload();
        $this->assertSame('Flus news', $feed->nameByUser($user));
        $this->assertSame('Carnet de Flus', $feed->name);
    }

    public function testUpdateResetsTheNameIfEmpty(): void
    {
        $user = $this->login();
        $feed = FeedFactory::create([
            'name' => 'Carnet de Flus',
            'is_public' => true,
        ]);
        $follow = $user->follow($feed);
        $follow->name = 'Flus news';
        $follow->save();

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
            'name' => '',
            'time_filter' => 'normal',
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$feed->id}/follow/edit");
        $user = $user->reload();
        $this->assertSame('Carnet de Flus', $feed->nameByUser($user));
    }

    public function testUpdateAttachesTheSelectedStreams(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->addSource($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $kept_stream = StreamFactory::create(['user_id' => $user->id]);
        $detached_stream = StreamFactory::create(['user_id' => $user->id]);
        $attached_stream = StreamFactory::create(['user_id' => $user->id]);
        $kept_stream->addSource($collection);
        $detached_stream->addSource($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'My stream',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => 'My stream',
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
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
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $follow = $user->follow($collection);
        $stream_name = str_repeat('a', models\Stream::NAME_MAX_LENGTH + 1);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
            'time_filter' => 'strict',
            'new_stream_name' => $stream_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than 100 characters.');
        $this->assertSame(0, models\Stream::count());
        $follow = $follow->reload();
        $this->assertSame('normal', $follow->time_filter);
    }

    public function testUpdateFailsIfStreamIsOwnedByAnotherUser(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $user->follow($collection);
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
            'time_filter' => 'normal',
            'stream_ids' => [$stream->id],
        ]);
        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'One of the selected streams doesn’t exist.');
        $this->assertFalse($stream->hasSource($collection));
    }

    public function testUpdateFailsIfNameIsTooLong(): void
    {
        $user = $this->login();
        $feed = FeedFactory::create([
            'name' => 'Carnet de Flus',
            'is_public' => true,
        ]);
        $user->follow($feed);

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
            'name' => str_repeat('a', models\Collection::NAME_MAX_LENGTH + 1),
            'time_filter' => 'normal',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than');
        $user = $user->reload();
        $this->assertSame('Carnet de Flus', $feed->nameByUser($user));
    }

    public function testUpdateFailsIfTimeFilterIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $follow = $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollow::class),
            'time_filter' => 'invalid',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The filter is invalid');
        $follow = $follow->reload();
        $this->assertSame('normal', $follow->time_filter);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $follow = $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/edit", [
            'csrf_token' => 'not the token',
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $follow = $follow->reload();
        $this->assertSame('normal', $follow->time_filter);
    }

    public function testCreateMakesUserFollowingAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $this->assertSame(0, models\Follow::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\sources\Follow::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(1, models\Follow::count());
        $follow = models\Follow::take();
        $this->assertNotNull($follow);
        $this->assertSame($user->id, $follow->user_id);
        $this->assertSame($collection->id, $follow->collection_id);
    }

    public function testCreateWorksIfSourceIsShared(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\sources\Follow::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(1, models\Follow::count());
        $follow = models\Follow::take();
        $this->assertNotNull($follow);
        $this->assertSame($user->id, $follow->user_id);
        $this->assertSame($collection->id, $follow->collection_id);
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\sources\Follow::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertSame(0, models\Follow::count());
    }

    public function testCreateFailsIfSourceDoesNotExist(): void
    {
        $this->login();
        $owner = UserFactory::create();
        CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', '/collections/unknown/follow', [
            'csrf_token' => $this->csrfToken(forms\sources\Follow::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Follow::count());
    }

    public function testCreateFailsIfUserHasNoAccess(): void
    {
        $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => $this->csrfToken(forms\sources\Follow::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\Follow::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertSame(0, models\Follow::count());
    }

    public function testDeleteMakesUserUnfollowingAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\sources\Unfollow::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\Follow::count());
    }

    public function testDeleteWorksIfSourceIsShared(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);
        $user->follow($collection);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\sources\Unfollow::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\Follow::count());
    }

    public function testDeleteWorksIfUserHasNoAccessToTheSource(): void
    {
        // This can happen if a user follow a collection, but its owner change
        // the visibility.
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => false,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\sources\Unfollow::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\Follow::count());
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => $this->csrfToken(forms\sources\Unfollow::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertSame(1, models\Follow::count());
    }

    public function testDeleteFailsIfSourceDoesNotExist(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', '/collections/unknown/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\Unfollow::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(1, models\Follow::count());
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertSame(1, models\Follow::count());
    }

    public function testDeleteSelectedMakesUserUnfollowingTheSourcesAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source_1 = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source_1);
        $user->follow($source_2);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source_1->id, $source_2->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($source_1));
        $this->assertFalse($user->isFollowing($source_2));
    }

    public function testDeleteSelectedRemovesTheSourcesFromTheStreams(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->addSource($source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($source));
        $this->assertFalse($stream->hasSource($source));
        $this->assertTrue(models\Stream::exists($stream->id));
    }

    public function testDeleteSelectedDoesNotUnfollowUnselectedSources(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $selected_source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $other_source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($selected_source);
        $user->follow($other_source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$selected_source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($selected_source));
        $this->assertTrue($user->isFollowing($other_source));
    }

    public function testDeleteSelectedIgnoresNotFollowedSources(): void
    {
        $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\Follow::count());
    }

    public function testDeleteSelectedDoesNotUnfollowSourcesOfOtherUsers(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $other_user->follow($source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($other_user->isFollowing($source));
    }

    public function testDeleteSelectedIgnoresUnknownSourceIds(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id, 'unknown'],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($source));
    }

    public function testDeleteSelectedDoesNothingIfSourceIdsIsEmpty(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->isFollowing($source));
    }

    public function testDeleteSelectedRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue($user->isFollowing($source));
    }

    public function testDeleteSelectedFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => 'not the token',
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertTrue($user->isFollowing($source));
    }

    public function testUpdateSelectedTimeFilterChangesTheTimeFilterAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source_1 = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source_1);
        $user->follow($source_2);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source_1->id, $source_2->id],
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('strict', $user->findFollow($source_1)?->time_filter);
        $this->assertSame('strict', $user->findFollow($source_2)?->time_filter);
    }

    public function testUpdateSelectedTimeFilterDoesNotChangeUnselectedSources(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $selected_source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $other_source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($selected_source);
        $user->follow($other_source);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$selected_source->id],
            'time_filter' => 'all',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('all', $user->findFollow($selected_source)?->time_filter);
        $this->assertSame('normal', $user->findFollow($other_source)?->time_filter);
    }

    public function testUpdateSelectedTimeFilterDoesNotChangeSourcesOfOtherUsers(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $other_user->follow($source);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('normal', $other_user->findFollow($source)?->time_filter);
    }

    public function testUpdateSelectedTimeFilterDoesNothingIfSourceIdsIsEmpty(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('normal', $user->findFollow($source)?->time_filter);
    }

    public function testUpdateSelectedTimeFilterFailsIfTimeFilterIsInvalid(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
            'time_filter' => 'invalid',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('The filter is invalid', $error);
        $this->assertSame('normal', $user->findFollow($source)?->time_filter);
    }

    public function testUpdateSelectedTimeFilterFailsIfTimeFilterIsMissing(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('The filter is required', $error);
        $this->assertSame('normal', $user->findFollow($source)?->time_filter);
    }

    public function testUpdateSelectedTimeFilterRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertSame('normal', $user->findFollow($source)?->time_filter);
    }

    public function testUpdateSelectedTimeFilterFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'is_public' => true,
        ]);
        $user->follow($source);

        $response = $this->appRun('POST', '/sources/time-filter', [
            'csrf_token' => 'not the token',
            'source_ids' => [$source->id],
            'time_filter' => 'strict',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertSame('normal', $user->findFollow($source)?->time_filter);
    }
}
