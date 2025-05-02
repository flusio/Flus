<?php

namespace App\controllers\api\v1\links;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\NoteFactory;
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
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
        ]);

        $response = $this->apiRun('GET', "/api/v1/links/{$link->id}/notes");

        $this->assertResponseCode($response, 200);
        $this->assertApiResponse($response, [
            $note->toJson(),
        ]);
    }

    public function testIndexFailsIfTheLinkDoesNotExist(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);
        $note = NoteFactory::create([
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
        $note = NoteFactory::create([
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
        $note = NoteFactory::create([
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
        $this->assertSame(1, models\Note::count());
        $note = models\Note::take();
        $this->assertNotNull($note);
        $this->assertSame($content, $note->content);
        $this->assertSame($link->id, $note->link_id);
        $this->assertSame($user->id, $note->user_id);
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
        $this->assertSame(0, models\Note::count());
        $this->assertApiError(
            $response,
            'content',
            ['presence', 'The content is required.']
        );
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
        $this->assertSame(0, models\Note::count());
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
        $this->assertSame(0, models\Note::count());
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
        $this->assertSame(0, models\Note::count());
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
    }
}
