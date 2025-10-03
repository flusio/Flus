<?php

namespace App\controllers\api\v1;

use App\auth;
use App\models;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class SearchesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;
    use \tests\MockHttpHelper;

    public function testCreateCreatesALinkAndReturnsIt(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->apiRun('POST', '/api/v1/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, models\Link::count());
        $link = models\Link::take();
        $this->assertNotNull($link);
        $this->assertSame($user->id, $link->user_id);
        $this->assertSame($url, $link->url);
        $this->assertApiResponse($response, [
            'links' => [$link->toJson($user)],
        ]);
    }

    public function testCreateReusesExistingLinks(): void
    {
        $user = $this->login();
        $url = 'https://flus.fr/carnet/';
        $title = 'My title';
        $reading_time = 42;
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'url' => $url,
            'title' => $title,
            'reading_time' => $reading_time,
        ]);

        $response = $this->apiRun('POST', '/api/v1/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, models\Link::count());
        $this->assertApiResponse($response, [
            'links' => [$link->toJson($user)],
        ]);
    }

    public function testCreateFailsIfUrlIsInvalid(): void
    {
        $user = $this->login();
        $url = 'about:newtab';

        $response = $this->apiRun('POST', '/api/v1/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertSame(0, models\Link::count());
        $this->assertApiError(
            $response,
            'url',
            ['url', 'The link is invalid.']
        );
    }

    public function testCreateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $url = 'https://flus.fr/carnet/';
        $this->mockHttpWithFixture($url, 'responses/flus.fr_carnet_index.html');

        $response = $this->apiRun('POST', '/api/v1/search', [
            'url' => $url,
        ]);

        $this->assertResponseCode($response, 401);
        $this->assertSame(0, models\Link::count());
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
