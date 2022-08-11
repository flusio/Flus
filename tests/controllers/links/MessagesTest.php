<?php

namespace flusio\controllers\links;

use flusio\models;

class MessagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testIndexRedirects()
    {
        $response = $this->appRun('get', '/links/an_id/messages');

        $this->assertResponseCode($response, 302, '/links/an_id');
    }

    public function testCreateCreatesMessageAndRedirects()
    {
        $user = $this->login();
        $user_id = $user->id;
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = $user->csrf;
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Message::count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link_id}");
        $this->assertSame(1, models\Message::count());
        $message = models\Message::take();
        $this->assertSame($content, $message->content);
        $this->assertSame($user_id, $message->user_id);
        $this->assertSame($link_id, $message->link_id);
    }

    public function testCreateWorksIfLinkIsInCollectionSharedWithWriteAccess()
    {
        $user = $this->login();
        $user_id = $user->id;
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
            'type' => 'write',
        ]);
        $csrf = $user->csrf;
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Message::count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link_id}");
        $this->assertSame(1, models\Message::count());
        $message = models\Message::take();
        $this->assertSame($content, $message->content);
        $this->assertSame($user_id, $message->user_id);
        $this->assertSame($link_id, $message->link_id);
    }

    public function testCreateRedirectsIfNotConnected()
    {
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

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link_id}");
        $this->assertSame(0, models\Message::count());
    }

    public function testCreateFailsIfLinkIsNotOwned()
    {
        $user = $this->login();
        $user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = $user->csrf;
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Message::count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Message::count());
    }

    public function testCreateFailsIfLinkIsInCollectionSharedWithReadAccess()
    {
        $user = $this->login();
        $user_id = $user->id;
        $other_user_id = $this->create('user');
        $link_id = $this->create('link', [
            'user_id' => $other_user_id,
        ]);
        $collection_id = $this->create('collection', [
            'user_id' => $other_user_id,
        ]);
        $this->create('link_to_collection', [
            'link_id' => $link_id,
            'collection_id' => $collection_id,
        ]);
        $this->create('collection_share', [
            'collection_id' => $collection_id,
            'user_id' => $user_id,
            'type' => 'read',
        ]);
        $csrf = $user->csrf;
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Message::count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Message::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $user_id = $user->id;
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = 'not the token';
        $content = $this->fake('paragraphs', 3, true);

        $this->assertSame(0, models\Message::count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Message::count());
    }

    public function testCreateFailsIfContentIsEmpty()
    {
        $user = $this->login();
        $user_id = $user->id;
        $link_id = $this->create('link', [
            'user_id' => $user_id,
        ]);
        $csrf = $user->csrf;
        $content = '';

        $this->assertSame(0, models\Message::count());

        $response = $this->appRun('post', "/links/{$link_id}/messages", [
            'csrf' => $csrf,
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The message is required');
        $this->assertSame(0, models\Message::count());
    }
}
