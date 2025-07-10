<?php

namespace App\controllers\api\v1\links;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\MessageFactory;
use tests\factories\UserFactory;

class NotesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testIndexReturnsNotesOfTheLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
        ]);

        $response = $this->apiRun('GET', "/api/v1/links/{$link->id}/notes");

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, [
            $message->toJson(),
        ]);
    }

    public function testIndexFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
        ]);

        $response = $this->apiRun('GET', '/api/v1/links/not-an-id/notes');

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testIndexFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $other_user->id,
            'is_hidden' => true,
        ]);
        $message = MessageFactory::create([
            'user_id' => $other_user->id,
            'link_id' => $link->id,
        ]);

        $response = $this->apiRun('GET', "/api/v1/links/{$link->id}/notes");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot list the notes of the link.',
        ]);
    }

    public function testIndexFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $message = MessageFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
        ]);

        $response = $this->apiRun('GET', "/api/v1/links/{$link->id}/notes");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }

    public function testCreateAddsANoteToTheLink(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = 'My note';

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/notes", [
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertSame(1, models\Message::count());
        $message = models\Message::take();
        $this->assertSame($content, $message->content);
        $this->assertSame($link->id, $message->link_id);
        $this->assertSame($user->id, $message->user_id);
    }

    public function testCreateFailsIfTheContentIsInvalid(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = '';

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/notes", [
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertSame(0, models\Message::count());
        $this->assertApiError($response, 'content', [
            'error' => 'You cannot update the link.',
        ]);
    }

    public function testCreateFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);
        $content = 'My note';

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/notes", [
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\Message::count());
        $this->assertApiResponse($response, [
            'error' => 'You cannot add notes to the link.',
        ]);
    }

    public function testCreateFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        $content = 'My note';

        $response = $this->apiRun('POST', '/api/v1/links/not-an-id/notes', [
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(0, models\Message::count());
        $this->assertApiResponse($response, [
            'error' => 'The link does not exist.',
        ]);
    }

    public function testCreateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $content = 'My note';

        $response = $this->apiRun('POST', "/api/v1/links/{$link->id}/notes", [
            'content' => $content,
        ]);

        $this->assertResponseCode($response, 401);
        $this->assertSame(0, models\Message::count());
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
