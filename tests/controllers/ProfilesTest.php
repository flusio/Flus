<?php

namespace App\controllers;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;
use tests\factories\UserFactory;

class ProfilesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/show.phtml');
        $this->assertResponseContains($response, $username);
    }

    public function testShowDisplaysAnEditButtonIfConnectedToItsOwnPage(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', "/p/{$user->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/show.phtml');
        $this->assertResponseContains($response, 'Edit');
    }

    public function testShowDisplaysPublicCollectionsWithLinks(): void
    {
        /** @var string */
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        /** @var string */
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

    public function testShowDisplaysLastSharedLinks(): void
    {
        $user = UserFactory::create();
        /** @var string */
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

    public function testShowDisplaysSharedCollections(): void
    {
        $current_user = $this->login();
        /** @var string */
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        /** @var string */
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

    public function testShowDoesNotDisplayPublicCollectionsWithOnlyHiddenLinks(): void
    {
        /** @var string */
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        /** @var string */
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

    public function testShowDoesNotDisplayPrivateCollectionsWithLinks(): void
    {
        /** @var string */
        $username = $this->fake('username');
        $user = UserFactory::create([
            'username' => $username,
        ]);
        /** @var string */
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

    public function testShowFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserIsSupportUser(): void
    {
        $support_user = models\User::supportUser();

        $response = $this->appRun('GET', "/p/{$support_user->id}");

        $this->assertResponseCode($response, 404);
    }
}
