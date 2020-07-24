<?php

namespace flusio;

class LinkMessagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRedirects()
    {
        $response = $this->appRun('get', '/links/an_id/messages');

        $this->assertResponse($response, 302, '/links/an_id');
    }

    public function testCreateCreatesMessageAndRedirects()
    {
        $message_dao = new models\dao\Message();
        $user = $this->login();
        $user_id = $user->id;
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = $user->csrf;
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, $message_dao->count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $this->assertSame(1, $message_dao->count());
        $db_message = $message_dao->listAll()[0];
        $this->assertSame($content, $db_message['content']);
        $this->assertSame($user_id, $db_message['user_id']);
        $this->assertSame($link_id, $db_message['link_id']);
    }

    public function testCreateRedirectsIfNotConnected()
    {
        $message_dao = new models\dao\Message();
        $csrf = 'the token';
        $user_id = $this->create('user', [
            'csrf' => $csrf,
        ]);
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $content = $this->fake('paragraphs', 3, true);

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponse($response, 302, "/links/{$link_id}");
        $this->assertSame(0, $message_dao->count());
    }

    public function testCreateFailsIfLinkIsNotOwned()
    {
        $message_dao = new models\dao\Message();
        $user = $this->login();
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = $user->csrf;
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, $message_dao->count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponse($response, 404);
        $this->assertSame(0, $message_dao->count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $message_dao = new models\dao\Message();
        $user = $this->login();
        $user_id = $user->id;
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = 'not the token';
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, $message_dao->count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, $message_dao->count());
    }

    public function testCreateFailsIfContentIsEmpty()
    {
        $message_dao = new models\dao\Message();
        $user = $this->login();
        $user_id = $user->id;
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = $user->csrf;
        $content = '';

        $this->assertSame(0, $message_dao->count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponse($response, 400, 'The message is required');
        $this->assertSame(0, $message_dao->count());
    }
}
