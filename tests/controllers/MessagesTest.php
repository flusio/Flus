<?php

namespace App\controllers;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\MessageFactory;
use tests\factories\UserFactory;

class MessagesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
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

    public function testEditRedirectsToLoginIfNotConnected(): void
    {
        /** @var string */
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

    public function testEditFailsIfMessageDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
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

    public function testEditFailsIfUserHasNoAccessToMessage(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
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

    public function testUpdateChangesContentAndRedirect(): void
    {
        $user = $this->login();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testUpdateChangesLinkTags(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'tags' => ['foo' => 'foo'],
        ]);
        $old_content = '#foo';
        $new_content = '#Bar';
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
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
        $link = $link->reload();
        $this->assertEquals(['bar' => 'Bar'], $link->tags);
    }

    public function testUpdateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testUpdateFailsIfMessageDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testUpdateFailsIfUserHasNoAccessToMessage(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testUpdateFailsIfContentIsEmpty(): void
    {
        $user = $this->login();
        /** @var string */
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

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
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

    public function testDeleteDeletesMessageAndRedirects(): void
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

    public function testDeleteChangesLinkTags(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'tags' => ['foo'],
        ]);
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
            'content' => '#foo',
        ]);

        $response = $this->appRun('POST', "/messages/{$message->id}/delete", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Message::exists($message->id));
        $link = $link->reload();
        $this->assertEquals([], $link->tags);
    }

    public function testDeleteRedirectsToRedirectToIfGiven(): void
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

    public function testDeleteRedirectsIfNotConnected(): void
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

    public function testDeleteFailsIfMessageIsNotOwned(): void
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

    public function testDeleteFailsIfCsrfIsInvalid(): void
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
