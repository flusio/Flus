<?php

namespace App\controllers\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\UserFactory;

class FollowersTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testCreateMakesUserFollowingAndRedirects(): void
    {
        $user = $this->login();
        $owner = UserFactory::create();
        $collection = CollectionFactory::create([
            'user_id' => $owner->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $this->assertSame(0, models\FollowedCollection::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', '/collections/unknown/follow', [
            'csrf' => $user->csrf,
            'from' => $from,
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow", [
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertSame(
            'A security verification failed: you should retry to submit the form.',
            \Minz\Flash::get('error'),
        );
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', '/collections/unknown/unfollow', [
            'csrf' => $user->csrf,
            'from' => $from,
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/unfollow", [
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertSame(
            'A security verification failed: you should retry to submit the form.',
            \Minz\Flash::get('error'),
        );
        $this->assertSame(1, models\FollowedCollection::count());
    }
}
