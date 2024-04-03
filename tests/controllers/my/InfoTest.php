<?php

namespace App\controllers\my;

use tests\factories\LinkFactory;
use tests\factories\LinkToCollectionFactory;

class InfoTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $url_1 = $this->fakeUnique('url');
        /** @var string */
        $url_2 = $this->fakeUnique('url');
        $bookmarks = $user->bookmarks();
        $link_1 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url_1,
        ]);
        $link_2 = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url_2,
        ]);
        LinkToCollectionFactory::create([
            'collection_id' => $bookmarks->id,
            'link_id' => $link_1->id,
        ]);

        $response = $this->appRun('GET', '/my/info.json');

        $this->assertResponseCode($response, 200);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/json',
        ]);
        $this->assertInstanceOf(\Minz\Response::class, $response);
        /** @var array<string, mixed> */
        $output = json_decode($response->render(), true);
        $this->assertSame($user->csrf, $output['csrf']);
        $this->assertSame($bookmarks->id, $output['bookmarks_id']);
        $this->assertSame([$url_1], $output['bookmarked_urls']);
    }

    public function testShowRedirectsIfUserNotConnected(): void
    {
        $response = $this->appRun('GET', '/my/info.json');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Finfo.json');
    }
}
