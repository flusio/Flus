<?php

namespace App\controllers\profiles;

use App\models;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class CollectionsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        $user = UserFactory::create();
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
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testShowDisplaysAnEditButtonIfConnectedToItsOwnPage(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseContains($response, 'Edit');
    }

    public function testShowDoesNotDisplayPublicCollectionsWithOnlyHiddenLinks(): void
    {
        $user = UserFactory::create();
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
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowDoesNotDisplayPrivateCollectionsWithLinks(): void
    {
        $user = UserFactory::create();
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
        $link->addCollection($collection);

        $response = $this->appRun('GET', "/p/{$user->id}/collections");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'profiles/collections/index.html.twig');
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id/collections');

        $this->assertResponseCode($response, 404);
    }

    public function testShowFailsIfUserIsSupportUser(): void
    {
        $support_user = models\User::supportUser();

        $response = $this->appRun('GET', "/p/{$support_user->id}/collections");

        $this->assertResponseCode($response, 404);
    }
}
