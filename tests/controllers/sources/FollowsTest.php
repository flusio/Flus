<?php

namespace App\controllers\sources;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
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

    public function testDeleteMakesUserUnfollowingTheSourcesAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source_1 = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $source_2 = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($source_1->id);
        $user->follow($source_2->id);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source_1->id, $source_2->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($source_1->id));
        $this->assertFalse($user->isFollowing($source_2->id));
    }

    public function testDeleteRemovesTheSourcesFromTheStreams(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($source->id);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $stream->addSource($source);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($source->id));
        $this->assertFalse($stream->hasSource($source));
        $this->assertTrue(models\Stream::exists($stream->id));
    }

    public function testDeleteDoesNotUnfollowUnselectedSources(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $selected_source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $other_source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($selected_source->id);
        $user->follow($other_source->id);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$selected_source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($selected_source->id));
        $this->assertTrue($user->isFollowing($other_source->id));
    }

    public function testDeleteIgnoresNotFollowedSources(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame(0, models\FollowedCollection::count());
    }

    public function testDeleteDoesNotUnfollowSourcesOfOtherUsers(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $other_user->follow($source->id);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($other_user->isFollowing($source->id));
    }

    public function testDeleteIgnoresUnknownSourceIds(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id, 'unknown'],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse($user->isFollowing($source->id));
    }

    public function testDeleteDoesNothingIfSourceIdsIsEmpty(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue($user->isFollowing($source->id));
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => $this->csrfToken(forms\sources\BulkSelection::class),
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue($user->isFollowing($source->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $source = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $user->follow($source->id);

        $response = $this->appRun('POST', '/sources/unfollow', [
            'csrf_token' => 'not the token',
            'source_ids' => [$source->id],
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertTrue($user->isFollowing($source->id));
    }
}
