<?php

namespace App\controllers\profiles;

use tests\factories\CollectionFactory;
use tests\factories\UserFactory;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;

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

        $response = $this->appRun('GET', "/p/{$user->id}/opml.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/opml/show.opml.xml.php');
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'text/x-opml',
        ]);
    }

    public function testShowDoesNotRenderPrivateCollections(): void
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

        $response = $this->appRun('GET', "/p/{$user->id}/opml.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowFailsIfUserDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/p/not-an-id/opml.xml');

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow(): void
    {
        $user = UserFactory::create();

        $response = $this->appRun('GET', "/p/{$user->id}/opml");

        $this->assertResponseCode($response, 301, "/p/{$user->id}/opml.xml");
    }
}
