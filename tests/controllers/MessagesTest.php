<?php

namespace flusio\controllers;

use flusio\models;

class MessagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testDeleteDeletesMessageAndRedirects()
    {
        $user = $this->login();
        $message_id = $this->create('message', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/messages/{$message_id}/delete", [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponse($response, 302, '/');
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

        $this->assertResponse($response, 302, '/bookmarks');
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

        $this->assertResponse($response, 302, '/login?redirect_to=%2F');
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

        $this->assertResponse($response, 404);
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

        $this->assertResponse($response, 302, '/');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\Message::exists($message_id));
    }
}
