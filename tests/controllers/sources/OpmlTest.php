<?php

namespace App\controllers\sources;

use tests\factories\CollectionFactory;
use tests\factories\StreamFactory;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        $this->freeze(new \DateTimeImmutable('2026-10-27'));
        $user = $this->login();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'name' => $collection_name,
            'is_public' => true,
        ]);
        /** @var string */
        $feed_name = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'name' => $feed_name,
            'is_public' => true,
        ]);
        $user->follow($collection);
        $user->follow($feed);
        $stream->addSource($feed);

        $response = $this->appRun('GET', '/sources/opml.xml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'text/x-opml',
            'Content-Disposition' => 'inline; filename="2026-10-27_Flus_sources.opml.xml"',
        ]);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseContains($response, $stream_name);
        $this->assertResponseContains($response, $feed_name);
    }

    public function testShowRedirectsIfNotConnected(): void
    {
        $redirect_to = urlencode('/sources/opml.xml');

        $response = $this->appRun('GET', '/sources/opml.xml');

        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }
}
