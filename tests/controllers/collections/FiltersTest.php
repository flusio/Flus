<?php

namespace flusio\controllers\collections;

use flusio\models;

class FiltersTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
            'name' => $collection_name,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/filter", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/filters/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
            'name' => $collection_name,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/filter", [
            'from' => $from,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$encoded_from}");
    }

    public function testEditFailsIfCollectionIsPrivate()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 0,
            'name' => $collection_name,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/filter", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
            'name' => $collection_name,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/filter", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesTimeFilterAndRedirectsCorrectly()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $followed_collection_id = $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/filter", [
            'csrf' => $user->csrf,
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $followed_collection = models\FollowedCollection::find($followed_collection_id);
        $this->assertSame($new_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $other_user_id = $this->create('user');
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $followed_collection_id = $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/filter", [
            'csrf' => $user->csrf,
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $encoded_from = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$encoded_from}");
        $followed_collection = models\FollowedCollection::find($followed_collection_id);
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfCollectionIsPrivate()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 0,
        ]);
        $followed_collection_id = $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/filter", [
            'csrf' => $user->csrf,
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 404);
        $followed_collection = models\FollowedCollection::find($followed_collection_id);
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfCollectionIsNotFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/filter", [
            'csrf' => $user->csrf,
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = $time_filters[0];
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $followed_collection_id = $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/filter", [
            'csrf' => 'not the token',
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $followed_collection = models\FollowedCollection::find($followed_collection_id);
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfTimeFilterIsInvalid()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $new_time_filter = 'invalid time filter';
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $followed_collection_id = $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/filter", [
            'csrf' => $user->csrf,
            'from' => $from,
            'time_filter' => $new_time_filter,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The filter is invalid');
        $followed_collection = models\FollowedCollection::find($followed_collection_id);
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }

    public function testUpdateFailsIfTimeFilterIsMissing()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $time_filters = models\FollowedCollection::VALID_TIME_FILTERS;
        shuffle($time_filters);
        $old_time_filter = array_pop($time_filters);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $followed_collection_id = $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'time_filter' => $old_time_filter,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/filter", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The filter is required');
        $followed_collection = models\FollowedCollection::find($followed_collection_id);
        $this->assertSame($old_time_filter, $followed_collection->time_filter);
    }
}
