<?php

namespace flusio\controllers;

use flusio\models;
use tests\factories\MessageFactory;
use tests\factories\UserFactory;

class MessagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $content = $this->fake('sentence');
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', "/messages/{$message->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'messages/edit.phtml');
        $this->assertResponseContains($response, $content);
    }

    public function testEditRedirectsToLoginIfNotConnected()
    {
        $content = $this->fake('sentence');
        $message = MessageFactory::create([
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', "/messages/{$message->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
    }

    public function testEditFailsIfMessageDoesNotExist()
    {
        $user = $this->login();
        $content = $this->fake('sentence');
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', '/messages/not-an-id/edit', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserHasNoAccessToMessage()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $content = $this->fake('sentence');
        $message = MessageFactory::create([
            'user_id' => $other_user->id,
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', "/messages/{$message->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateChangesContentAndRedirect()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/messages/{$message->id}/edit", [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $message = $message->reload();
        $this->assertSame($new_content, $message->content);
    }

    public function testUpdateRedirectsToLoginIfNotConnected()
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/messages/{$message->id}/edit", [
            'content' => $new_content,
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $message = $message->reload();
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfMessageDoesNotExist()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', '/messages/not-an-id/edit', [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $message = $message->reload();
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfUserHasNoAccessToMessage()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message = MessageFactory::create([
            'user_id' => $other_user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/messages/{$message->id}/edit", [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
        $message = $message->reload();
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfContentIsEmpty()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = '';
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/messages/{$message->id}/edit", [
            'content' => $new_content,
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertEquals([
            'content' => 'The message is required.',
        ], \Minz\Flash::get('errors'));
        $message = $message->reload();
        $this->assertSame($old_content, $message->content);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $old_content = $this->fakeUnique('sentence');
        $new_content = $this->fakeUnique('sentence');
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/messages/{$message->id}/edit", [
            'content' => $new_content,
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $message = $message->reload();
        $this->assertSame($old_content, $message->content);
    }

    public function testDeleteDeletesMessageAndRedirects()
    {
        $user = $this->login();
        $message = MessageFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/messages/{$message->id}/delete", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Message::exists($message->id));
    }

    public function testDeleteRedirectsToRedirectToIfGiven()
    {
        $user = $this->login();
        $message = MessageFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/messages/{$message->id}/delete", [
            'csrf' => $user->csrf,
            'redirect_to' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\Message::exists($message->id));
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $message = MessageFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/messages/{$message->id}/delete", [
            'csrf' => 'a token',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue(models\Message::exists($message->id));
    }

    public function testDeleteFailsIfMessageIsNotOwned()
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $message = MessageFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/messages/{$message->id}/delete", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Message::exists($message->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $message = MessageFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/messages/{$message->id}/delete", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertTrue(models\Message::exists($message->id));
    }
}
