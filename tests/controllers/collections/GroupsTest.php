<?php

namespace App\controllers\collections;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\FollowedCollectionFactory;
use tests\factories\GroupFactory;
use tests\factories\UserFactory;

class GroupsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRendersGroupIfAlreadySet(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseContains($response, $group_name);
    }

    public function testEditRendersIfCollectionIsFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'name' => $collection_name,
            'is_public' => true,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRendersGroupIfAlreadySetAndCollectionIsFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $group_name = $this->fakeUnique('text', 50);
        /** @var string */
        $other_group_name = $this->fakeUnique('text', 50);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $other_group = GroupFactory::create([
            'user_id' => $other_user->id,
            'name' => $other_group_name,
        ]);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
            'group_id' => $other_group->id,
        ]);
        FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'group_id' => $group->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseContains($response, $group_name);
        $this->assertResponseNotContains($response, $other_group_name);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/group", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testEditFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', '/collections/not-an-id/group', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
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
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/group", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateRedirectsToFrom(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 302, $from);
    }

    public function testUpdateCreatesGroup(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $this->assertSame(0, models\Group::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertSame(1, models\Group::count());
        $group = models\Group::take();
        $this->assertNotNull($group);
        $this->assertSame($group_name, $group->name);
        $this->assertSame($user->id, $group->user_id);
        $collection = $collection->reload();
        $this->assertSame($group->id, $collection->group_id);
    }

    public function testUpdateSetsGroupIfCollectionIsFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'user_id' => $other_user->id,
            'is_public' => true,
            'group_id' => null,
        ]);
        $followed_collection = FollowedCollectionFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $this->assertSame(0, models\Group::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertSame(1, models\Group::count());
        $collection = $collection->reload();
        $this->assertNull($collection->group_id);
        $group = models\Group::take();
        $this->assertNotNull($group);
        $followed_collection = $followed_collection->reload();
        $this->assertSame($group->id, $followed_collection->group_id);
    }

    public function testUpdateUnsetsGroupIfNameIsEmpty(): void
    {
        $user = $this->login();
        $group = GroupFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => '',
        ]);

        $this->assertResponseCode($response, 302, $from);
        $collection = $collection->reload();
        $this->assertNull($collection->group_id);
    }

    public function testUpdateDoesNotCreateGroupIfNameExists(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => null,
        ]);
        $group = GroupFactory::create([
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $this->assertSame(1, models\Group::count());

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertSame(1, models\Group::count());
        $collection = $collection->reload();
        $this->assertSame($group->id, $collection->group_id);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => 'a token',
            'from' => $from,
            'name' => $group_name,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/not-an-id/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCollectionIsNotFollowed(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => true,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'group_id' => null,
        ]);
        CollectionShareFactory::create([
            'user_id' => $user->id,
            'collection_id' => $collection->id,
            'type' => 'write',
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfNameIsInvalid(): void
    {
        $user = $this->login();
        $max_size = models\Group::NAME_MAX_LENGTH;
        $size = $max_size + 1;
        /** @var string */
        $group_name = $this->fake('regexify', "\w{{$size}}");
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, "The name must be less than {$max_size} characters");
        $this->assertSame(0, models\Group::count());
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $group_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'group_id' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection->id]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/group", [
            'csrf' => 'not the token',
            'from' => $from,
            'name' => $group_name,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/groups/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Group::count());
    }
}
