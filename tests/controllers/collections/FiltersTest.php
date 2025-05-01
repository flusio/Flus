<?php

namespace App\controllers\collections;

use App\models;
use tests\factories\UserFactory;
use tests\factories\CollectionFactory;
use tests\factories\FollowedCollectionFactory;

class FiltersTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
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
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/filter", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/filters/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
            'name' => $collection_name,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/filter", [
            'from' => $from,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$encoded_from}");
    }

    public function testEditFailsIfCollectionIsPrivate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
            'name' => $collection_name,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/filter", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotFollowed(): void
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/filter", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesTimeFilterAndRedirectsCorrectly(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $followed_collection = FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/filter", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $followed_collection = $followed_collection->reload();
        $this->assertSame($new_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $other_user = UserFactory::create();
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $followed_collection = FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/filter", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$encoded_from}");
        $followed_collection = $followed_collection->reload();
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfCollectionIsPrivate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $followed_collection = FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/filter", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 404);
        $followed_collection = $followed_collection->reload();
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfCollectionIsNotFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/filter", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $followed_collection = FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/filter", [
            'csrf' => 'not the token',
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $followed_collection = $followed_collection->reload();
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfTimeFilterIsInvalid(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = 'invalid time filter';
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $followed_collection = FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/filter", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The filter is invalid');
        $followed_collection = $followed_collection->reload();
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfTimeFilterIsMissing(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $followed_collection = FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/filter", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The filter is required');
        $followed_collection = $followed_collection->reload();
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }
}
