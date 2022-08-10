<?php

namespace flusio\controllers;

use flusio\models;

class MessagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $content = $this->fake('sentence');
        $message_id = $this->create('message', [
            'user_id' => $user->id,
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('get', "/messages/{$message_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'messages/edit.phtml');
        $this->assertResponseContains($response, $content);
    }

    public function testEditRedirectsToLoginIfNotConnected()
    {
        $content = $this->fake('sentence');
        $message_id = $this->create('message', [
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('get', "/messages/{$message_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
    }

    public function testEditFailsIfMessageDoesNotExist()
    {
        $user = $this->login();
        $content = $this->fake('sentence');
        $message_id = $this->create('message', [
            'user_id' => $user->id,
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('get', '/messages/not-an-id/edit', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserHasNoAccessToMessage()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $content = $this->fake('sentence');
        $message_id = $this->create('message', [
            'user_id' => $other_user_id,
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('get', "/messages/{$message_id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesContentAndRedirect()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message_id = $this->create('message', [
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('post', "/messages/{$message_id}/edit", [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $message = models\Message::find($message_id);
        $this->assertSame($new_content, $message->content);
    }

    public function testUpdateRedirectsToLoginIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message_id = $this->create('message', [
            'user_id' => $user_id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('post', "/messages/{$message_id}/edit", [
            'content' => $new_content,
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $message = models\Message::find($message_id);
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfMessageDoesNotExist()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message_id = $this->create('message', [
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('post', '/messages/not-an-id/edit', [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $message = models\Message::find($message_id);
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfUserHasNoAccessToMessage()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message_id = $this->create('message', [
            'user_id' => $other_user_id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('post', "/messages/{$message_id}/edit", [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $message = models\Message::find($message_id);
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfContentIsEmpty()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = '';
        $message_id = $this->create('message', [
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('post', "/messages/{$message_id}/edit", [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertFlash('errors', [
            'content' => 'The message is required.',
        ]);
        $message = models\Message::find($message_id);
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message_id = $this->create('message', [
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('post', "/messages/{$message_id}/edit", [
            'content' => $new_content,
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertFlash('error', 'A security verification failed.');
        $message = models\Message::find($message_id);
        $this->assertSame($old_content, $message->content);
    }

    public function testDeleteDeletesMessageAndRedirects()
    {
        $user = $this->login();
        $message_id = $this->create('message', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/messages/{$message_id}/delete", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Message::exists($message_id));
    }

    public function testDeleteRedirectsToRedirectToIfGiven()
    {
        $user = $this->login();
        $message_id = $this->create('message', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/messages/{$message_id}/delete", [
            'csrf' => $user->csrf,
            'redirect_to' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\Message::exists($message_id));
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $message_id = $this->create('message', [
            'user_id' => $user_id,
        ]);

        $response = $this->appRun('post', "/messages/{$message_id}/delete", [
            'csrf' => 'a token',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue(models\Message::exists($message_id));
    }

    public function testDeleteFailsIfMessageIsNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $message_id = $this->create('message', [
            'user_id' => $other_user_id,
        ]);

        $response = $this->appRun('post', "/messages/{$message_id}/delete", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Message::exists($message_id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $message_id = $this->create('message', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/messages/{$message_id}/delete", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\Message::exists($message_id));
    }
}
