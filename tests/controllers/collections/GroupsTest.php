<?php

namespace flusio\controllers\collections;

use flusio\models;

class GroupsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRendersGroupIfAlreadySet()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => $group_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseContains($response, $group_name);
    }

    public function testEditRendersIfCollectionIsFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'name' => $collection_name,
            'is_public' => 1,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRendersGroupIfAlreadySetAndCollectionIsFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $group_name = $this->fakeUnique('text', 50);
        $other_group_name = $this->fakeUnique('text', 50);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $other_group_id = $this->create('group', [
            'user_id' => $other_user_id,
            'name' => $other_group_name,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'is_public' => 1,
            'group_id' => $other_group_id,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'group_id' => $group_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseContains($response, $group_name);
        $this->assertResponseNotContains($response, $other_group_name);
    }

    public function testEditRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/group", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testEditFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', '/collections/not-an-id/group', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
        ]);
        $this->create('collection_share', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateRedirectsToFrom()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 302, $from);
    }

    public function testUpdateCreatesGroup()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $this->assertSame(0, models\Group::count());

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertSame(1, models\Group::count());
        $group = models\Group::take();
        $this->assertSame($group_name, $group->name);
        $this->assertSame($user->id, $group->user_id);
        $collection = models\Collection::find($collection_id);
        $this->assertSame($group->id, $collection->group_id);
    }

    public function testUpdateSetsGroupIfCollectionIsFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'is_public' => 1,
            'group_id' => null,
        ]);
        $followed_collection_id = $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $this->assertSame(0, models\Group::count());

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertSame(1, models\Group::count());
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->group_id);
        $group = models\Group::take();
        $followed_collection = models\FollowedCollection::find($followed_collection_id);
        $this->assertSame($group->id, $followed_collection->group_id);
    }

    public function testUpdateUnsetsGroupIfNameIsEmpty()
    {
        $user = $this->login();
        $group_id = $this->create('group');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => $group_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => '',
        ]);

        $this->assertResponseCode($response, 302, $from);
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->group_id);
    }

    public function testUpdateDoesNotCreateGroupIfNameExists()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => null,
        ]);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $this->assertSame(1, models\Group::count());

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertSame(1, models\Group::count());
        $collection = models\Collection::find($collection_id);
        $this->assertSame($group_id, $collection->group_id);
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user_id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => 'a token',
            'from' => $from,
            'name' => $group_name,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/not-an-id/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCollectionIsNotFollowed()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'is_public' => 1,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCollectionIsSharedWithWriteAccess()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $other_user_id,
            'group_id' => null,
        ]);
        $this->create('collection_share', [
            'user_id' => $user->id,
            'collection_id' => $collection_id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfNameIsInvalid()
    {
        $user = $this->login();
        $max_size = models\Group::NAME_MAX_LENGTH;
        $size = $max_size + 1;
        $group_name = $this->fake('regexify', "\w{{$size}}");
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => $user->csrf,
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, "The name must be less than {$max_size} characters");
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $group_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/group", [
            'csrf' => 'not the token',
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Group::count());
    }
}
