<?php

namespace flusio\controllers\profiles;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
            'name' => $collection_name,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}/opml.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/opml/show.opml.xml.php');
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'text/x-opml',
        ]);
    }

    public function testShowDoesNotRenderPrivateCollections()
    {
        $user_id = $this->create('user');
        $collection_name = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 0,
            'name' => $collection_name,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}/opml.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowFailsIfUserDoesNotExist()
    {
        $response = $this->appRun('get', '/p/not-an-id/opml.xml');

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow()
    {
        $user_id = $this->create('user');

        $response = $this->appRun('get', "/p/{$user_id}/opml");

        $this->assertResponseCode($response, 301, "/p/{$user_id}/opml.xml");
    }
}
