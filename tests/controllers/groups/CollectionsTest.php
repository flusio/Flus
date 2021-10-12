<?php

namespace flusio\controllers\groups;

use flusio\models;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRendersCorrectly()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $group_name = $this->fakeUnique('word');
        $collection_name = $this->fakeUnique('words', 3, true);
        $followed_collection_name = $this->fakeUnique('words', 3, true);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $collection_name,
            'group_id' => $group_id,
        ]);
        $followed_collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'name' => $followed_collection_name,
            'is_public' => true,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $followed_collection_id,
            'group_id' => $group_id,
        ]);

        $response = $this->appRun('get', "/groups/{$group_id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'groups/collections/index.phtml');
        $this->assertResponseContains($response, $group_name);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseContains($response, $followed_collection_name);
    }

    public function testIndexRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $other_user_id = $this->create('user');
        $group_name = $this->fakeUnique('word');
        $collection_name = $this->fakeUnique('words', 3, true);
        $followed_collection_name = $this->fakeUnique('words', 3, true);
        $group_id = $this->create('group', [
            'user_id' => $user->id,
            'name' => $group_name,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $collection_name,
            'group_id' => $group_id,
        ]);
        $followed_collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'name' => $followed_collection_name,
            'is_public' => true,
        ]);
        $this->create('followed_collection', [
            'user_id' => $user->id,
            'collection_id' => $followed_collection_id,
            'group_id' => $group_id,
        ]);

        $response = $this->appRun('get', "/groups/{$group_id}/collections");

        $encoded_url = urlencode("/groups/{$group_id}/collections");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$encoded_url}");
    }

    public function testIndexFailsIfNoAccessToTheGroup()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $group_name = $this->fakeUnique('word');
        $collection_name = $this->fakeUnique('words', 3, true);
        $followed_collection_name = $this->fakeUnique('words', 3, true);
        $group_id = $this->create('group', [
            'user_id' => $other_user_id,
            'name' => $group_name,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
            'type' => 'collection',
            'name' => $collection_name,
            'group_id' => $group_id,
        ]);
        $followed_collection_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'collection',
            'name' => $followed_collection_name,
            'is_public' => true,
        ]);
        $this->create('followed_collection', [
            'user_id' => $other_user_id,
            'collection_id' => $followed_collection_id,
            'group_id' => $group_id,
        ]);

        $response = $this->appRun('get', "/groups/{$group_id}/collections");

        $this->assertResponseCode($response, 404);
    }
}
