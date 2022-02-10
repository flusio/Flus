<?php

namespace flusio\controllers\collections;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'title' => $link_title,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $link_title);
        $this->assertResponsePointer($response, 'collections/feeds/show.atom.xml.php');
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function testShowDoesNotRenderHiddenLinks()
    {
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 1,
        ]);
        $link_id = $this->create('link', [
            'title' => $link_title,
            'is_hidden' => 1,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $link_title);
    }

    public function testShowFailsIfCollectionIsInaccessible()
    {
        $link_title = $this->fake('words', 3, true);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'is_public' => 0,
        ]);
        $link_id = $this->create('link', [
            'title' => $link_title,
            'is_hidden' => 0,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);

        $response = $this->appRun('get', "/collections/{$collection_id}/feed.atom.xml");

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow()
    {
        $collection_id = $this->create('collection');

        $response = $this->appRun('get', "/collections/{$collection_id}/feed");

        $this->assertResponseCode($response, 301, "/collections/{$collection_id}/feed.atom.xml");
    }
}
