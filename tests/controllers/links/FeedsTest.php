<?php

namespace flusio\controllers\links;

use tests\factories\LinkFactory;
use tests\factories\MessageFactory;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly(): void
    {
        /** @var string */
        $title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'title' => $title,
            'is_hidden' => false,
        ]);
        MessageFactory::create([
            'link_id' => $link->id,
            'content' => '**foo bar**',
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/feeds/show.atom.xml.php');
        $this->assertResponseContains($response, '<strong>foo bar</strong>');
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function testShowFailsIfLinkIsInaccessible(): void
    {
        /** @var string */
        $title = $this->fake('words', 3, true);
        $link = LinkFactory::create([
            'title' => $title,
            'is_hidden' => true,
        ]);
        /** @var string */
        $content = $this->fake('paragraphs', 3, true);
        MessageFactory::create([
            'link_id' => $link->id,
            'content' => $content,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/feed.atom.xml");

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow(): void
    {
        $link = LinkFactory::create();

        $response = $this->appRun('GET', "/links/{$link->id}/feed");

        $this->assertResponseCode($response, 301, "/links/{$link->id}/feed.atom.xml");
    }
}
