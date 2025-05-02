<?php

namespace App\controllers\api\v1;

use App\models;
use tests\factories\NoteFactory;
use tests\factories\UserFactory;

class NotesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;
    use \tests\ApiHelper;

    public function testUpdateChangesTheContent(): void
    {
        $user = $this->login();
        $old_content = 'My note';
        $new_content = 'The note';
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/notes/{$note->id}", [
            'content' => $new_content,
        ]);

        $this->assertResponseCode($response, 200);
        $note = $note->reload();
        $this->assertSame($new_content, $note->content);
    }

    public function testUpdateFailsIfContentIsEmpty(): void
    {
        $user = $this->login();
        $old_content = 'My note';
        $new_content = '';
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/notes/{$note->id}", [
            'content' => $new_content,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertApiError(
            $response,
            'content',
            ['presence', 'The content is required.']
        );
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testUpdateFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $old_content = 'My note';
        $new_content = 'The note';
        $note = NoteFactory::create([
            'user_id' => UserFactory::create()->id,
            'content' => $old_content,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/notes/{$note->id}", [
            'content' => $new_content,
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot update the note.',
        ]);
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testUpdateFailsIfTheNoteDoesNotExist(): void
    {
        $user = $this->login();
        $old_content = 'My note';
        $new_content = 'The note';
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/notes/not-an-id", [
            'content' => $new_content,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The note does not exist.',
        ]);
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testUpdateFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $old_content = 'My note';
        $new_content = 'The note';
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/notes/{$note->id}", [
            'content' => $new_content,
        ]);

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testDeleteRemovesTheNote(): void
    {
        $user = $this->login();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/notes/{$note->id}");

        $this->assertResponseCode($response, 200);
        $this->assertFalse(models\Note::exists($note->id));
    }

    public function testDeleteFailsIfPermissionIsNotGiven(): void
    {
        $user = $this->login();
        $note = NoteFactory::create([
            'user_id' => UserFactory::create()->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/notes/{$note->id}");

        $this->assertResponseCode($response, 403);
        $this->assertApiResponse($response, [
            'error' => 'You cannot delete the note.',
        ]);
        $this->assertTrue(models\Note::exists($note->id));
    }

    public function testDeleteFailsIfTheNoteDoesNotExist(): void
    {
        $user = $this->login();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->apiRun('DELETE', "/api/v1/notes/not-an-id");

        $this->assertResponseCode($response, 404);
        $this->assertApiResponse($response, [
            'error' => 'The note does not exist.',
        ]);
        $this->assertTrue(models\Note::exists($note->id));
    }

    public function testDeleteFailsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->apiRun('PATCH', "/api/v1/notes/{$note->id}");

        $this->assertResponseCode($response, 401);
        $this->assertApiResponse($response, [
            'error' => 'The request is not authenticated.',
        ]);
        $this->assertTrue(models\Note::exists($note->id));
    }
}
