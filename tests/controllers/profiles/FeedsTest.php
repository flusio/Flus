<?php

namespace flusio\controllers\profiles;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user_id = $this->create('user');
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'profiles/feeds/show.atom.xml.php');
        $this->assertResponseContains($response, $link_title);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/atom+xml',
        ]);
    }

    public function testShowDoesNotRenderHiddenLinks()
    {
        $user_id = $this->create('user');
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 1,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowDoesNotRenderVisibleLinksInPrivateCollections()
    {
        $user_id = $this->create('user');
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'user_id' => $user_id,
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
            'title' => $link_title,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $collection_id,
            'link_id' => $link_id,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowDoesNotRenderVisibleLinksInNoCollections()
    {
        $user_id = $this->create('user');
        $link_title = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
            'is_hidden' => 0,
            'title' => $link_title,
        ]);

        $response = $this->appRun('get', "/p/{$user_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowFailsIfUserDoesNotExist()
    {
        $response = $this->appRun('get', '/p/not-an-id/feed.atom.xml');

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow()
    {
        $user_id = $this->create('user');

        $response = $this->appRun('get', "/p/{$user_id}/feed");

        $this->assertResponseCode($response, 301, "/p/{$user_id}/feed.atom.xml");
    }
}
