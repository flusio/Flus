<?php

namespace flusio\controllers\links;

class FeedsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $title = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'title' => $title,
            'is_hidden' => 0,
        ]);
        $this->create('message', [
            'link_id' => $link_id,
            'content' => '**foo bar**',
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/feed.atom.xml");

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'links/feeds/show.atom.xml.php');
        $this->assertResponseContains($response, '<strong>foo bar</strong>');
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/xml',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function testShowFailsIfLinkIsInaccessible()
    {
        $title = $this->fake('words', 3, true);
        $link_id = $this->create('link', [
            'title' => $title,
            'is_hidden' => 1,
        ]);
        $content = $this->fake('paragraphs', 3, true);
        $this->create('message', [
            'link_id' => $link_id,
            'content' => $content,
        ]);

        $response = $this->appRun('get', "/links/{$link_id}/feed.atom.xml");

        $this->assertResponseCode($response, 404);
    }

    public function testAliasRedirectsToShow()
    {
        $link_id = $this->create('link');

        $response = $this->appRun('get', "/links/{$link_id}/feed");

        $this->assertResponseCode($response, 301, "/links/{$link_id}/feed.atom.xml");
    }
}
