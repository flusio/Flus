<?php

namespace flusio\controllers;

use flusio\models;

class ProfilesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $username = $this->fake('username');
        $user_id = $this->create('user', [
            'username' => $username,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/show.phtml');
        $this->assertResponseContains($response, $username);
    }

    public function testShowDisplaysAnEditButtonIfConnectedToItsOwnPage()
    {
        $user = $this->login();

        $response = $this->appRun('get', "/p/{$user->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/show.phtml');
        $this->assertResponseContains($response, 'Edit');
    }

    public function testShowDisplaysPublicCollectionsWithLinks()
    {
        $username = $this->fake('username');
        $user_id = $this->create('user', [
            'username' => $username,
        ]);
        $collection_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
            'name' => $collection_name,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}");

        $this->assertResponseContains($response, $collection_name);
    }

    public function testShowDoesNotDisplayPublicCollectionsWithOnlyHiddenLinks()
    {
        $username = $this->fake('username');
        $user_id = $this->create('user', [
            'username' => $username,
        ]);
        $collection_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
            'name' => $collection_name,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotDisplayPrivateCollectionsWithLinks()
    {
        $username = $this->fake('username');
        $user_id = $this->create('user', [
            'username' => $username,
        ]);
        $collection_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 0,
            'name' => $collection_name,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowFailsIfUserDoesNotExist()
    {
        $response = $this->appRun('get', '/p/not-an-id');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserIsSupportUser()
    {
        $support_user = models\User::supportUser();

        $response = $this->appRun('get', "/p/{$support_user->id}");

        $this->assertResponseCode($response, 404);
    }
}
