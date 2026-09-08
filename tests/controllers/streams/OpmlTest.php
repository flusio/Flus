<?php

namespace App\controllers\streams;

use tests\factories\CollectionFactory;
use tests\factories\StreamFactory;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => true,
            'name' => $collection_name,
        ]);
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($collection);

        $response = $this->appRun('GET', "/streams/{$stream->id}/opml.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/opml/show.opml.xml.twig');
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'text/x-opml',
        ]);
    }

    public function testShowDoesNotRenderPrivateSources(): void
    {
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'is_public' => false,
            'name' => $collection_name,
        ]);
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);
        $stream->addSource($collection);

        $response = $this->appRun('GET', "/streams/{$stream->id}/opml.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponseNotContains($response, $collection_name);
    }

    public function testShowFailsIfStreamIsInaccessible(): void
    {
        $stream = StreamFactory::create([
            'is_public' => false,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/opml.xml");

        $this->assertResponseCode($response, 403);
    }

    public function testShowFailsIfStreamDoesNotExist(): void
    {
        $response = $this->appRun('GET', '/streams/not-an-id/opml.xml');

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow(): void
    {
        $stream = StreamFactory::create([
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/opml");

        $this->assertResponseCode($response, 301, "/streams/{$stream->id}/opml.xml");
    }
}
