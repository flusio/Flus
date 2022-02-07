<?php

namespace flusio\controllers\my;

class InfoTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();
        $url_1 = $this->fakeUnique('url');
        $url_2 = $this->fakeUnique('url');
        $bookmarks_id = $this->create('collection', [
            'user_id' => $user->id,
            'type' => 'bookmarks',
        ]);
        $link_id_1 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url_1,
        ]);
        $link_id_2 = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url_2,
        ]);
        $this->create('link_to_collection', [
            'collection_id' => $bookmarks_id,
            'link_id' => $link_id_1,
        ]);

        $response = $this->appRun('get', '/my/info.json');

        $this->assertResponseCode($response, 200);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/json',
        ]);
        $output = json_decode($response->render(), true);
        $this->assertSame($user->csrf, $output['csrf']);
        $this->assertSame($bookmarks_id, $output['bookmarks_id']);
        $this->assertSame([$url_1], $output['bookmarked_urls']);
    }

    public function testShowRedirectsIfUserNotConnected()
    {
        $response = $this->appRun('get', '/my/info.json');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fmy%2Finfo.json');
    }
}
