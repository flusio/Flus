<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class ProfilesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/show.phtml');
        $this->assertResponseContains($response, $username);
    }

    public function testShowDisplaysAnEditButtonIfConnectedToItsOwnPage()
    {
        $user = $this->login();

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/show.phtml');
        $this->assertResponseContains($response, 'Edit');
    }

    public function testShowDisplaysPublicCollectionsWithLinks()
    {
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
            'name' => $collection_name,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseContains($response, $collection_name);
    }

    public function testShowDisplaysLastSharedLinks()
    {
        $user = UserFactory::create();
        $link_title = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
            'title' => $link_title,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseContains($response, $link_title);
    }

    public function testShowDisplaysSharedCollections()
    {
        $current_user = $this->login();
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
            'name' => $collection_name,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $current_user->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseContains($response, $collection_name);
    }

    public function testShowDoesNotDisplayPublicCollectionsWithOnlyHiddenLinks()
    {
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => true,
            'name' => $collection_name,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => true,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotDisplayPrivateCollectionsWithLinks()
    {
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'user_id' => $user->id,
            'type' => 'collection',
            'is_public' => false,
            'name' => $collection_name,
        ]);
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'is_hidden' => false,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $collection->id,
            'link_id' => $link->id,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowFailsIfUserDoesNotExist()
    {
        $response = $this->appRun('GET', '/p/not-an-id');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserIsSupportUser()
    {
        $support_user = models\User::supportUser();

        $response = $this->appRun('GET', "/p/{$support_user->id}");

        $this->assertResponseCode($response, 404);
    }
}
