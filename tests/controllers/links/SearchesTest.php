<?php

namespace flusio\controllers\links;

use flusio\models;

class SearchesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();
        $url = $this->fake('url');

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200, $url);
        $this->assertPointer($response, 'links/searches/show.phtml');
    }

    public function testShowDisplaysDefaultLink()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = $this->fake('url');
        $title = $this->fake('sentence');
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => 0,
            'title' => $title,
        ]);

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200, $title);
    }

    public function testShowDisplaysExistingLinkOverDefaultLink()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = $this->fake('url');
        $existing_title = $this->fakeUnique('sentence');
        $default_title = $this->fakeUnique('sentence');
        $existing_link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $existing_title,
        ]);
        $default_link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => 0,
            'title' => $default_title,
        ]);

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertStringContainsString($existing_title, $output);
        $this->assertStringNotContainsString($default_title, $output);
    }

    public function testShowDoesNotDisplaysHiddenDefaultLink()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = $this->fake('url');
        $title = $this->fake('sentence');
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'is_hidden' => 1,
            'title' => $title,
        ]);

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertStringNotContainsString($title, $output);
    }

    public function testShowRedirectsIfNotConnected()
    {
        $url = $this->fake('url');

        $response = $this->appRun('get', '/links/search', [
            'url' => $url,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Flinks%2Fsearch');
    }

    public function testCreateCreatesALinkAndFetchesIt()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $encoded_url = urlencode($url);
        $this->assertResponse($response, 302, "/links/search?url={$encoded_url}");
        $this->assertSame(1, models\Link::count());
        $link = models\Link::findBy(['url' => $url]);
        $this->assertSame($support_user->id, $link->user_id);
        $this->assertSame('flusio/flusio', $link->title);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateUpdatesDefaultLinkIfItExists()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'user_id' => $support_user->id,
            'url' => $url,
            'title' => $url,
            'fetched_code' => 0,
        ]);

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Link::count());
        $link = models\Link::find($link_id);
        $this->assertSame('flusio/flusio', $link->title);
        $this->assertSame(200, $link->fetched_code);
    }

    public function testCreateDoesNothingIfUserHasLink()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';
        $link_id = $this->create('link', [
            'user_id' => $user->id,
            'url' => $url,
            'title' => $url,
            'fetched_code' => 0,
        ]);

        $this->assertSame(1, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertSame(1, models\Link::count());
        $link = models\Link::find($link_id);
        $this->assertSame($url, $link->title);
        $this->assertSame(0, $link->fetched_code);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => 'a token',
            'url' => $url,
        ]);

        $this->assertResponse($response, 302, '/login?redirect_to=%2Flinks%2Fsearch');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = 'https://github.com/flusio/flusio';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => 'not the token',
            'url' => $url,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, models\Link::count());
    }

    public function testCreateFailsIfUrlIsInvalid()
    {
        $user = $this->login();
        $support_user = models\User::supportUser();
        $url = '';

        $this->assertSame(0, models\Link::count());

        $response = $this->appRun('post', '/links/search', [
            'csrf' => $user->csrf,
            'url' => $url,
        ]);

        $this->assertResponse($response, 400, 'The link is required.');
        $this->assertSame(0, models\Link::count());
    }
}
