<?php

namespace App\controllers;

use App\forms;
use App\models;
use tests\factories\LinkFactory;
use tests\factories\NoteFactory;
use tests\factories\UserFactory;

class NotesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $content = $this->fake('sentence');
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        $response = $this->appRun('GET', "/notes/{$note->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'notes/edit.html.twig');
        $this->assertResponseContains($response, $content);
    }

    public function testEditRedirectsToLoginIfNotConnected(): void
    {
        /** @var string */
        $content = $this->fake('sentence');
        $note = NoteFactory::create([
            'content' => $content,
        ]);

        $response = $this->appRun('GET', "/notes/{$note->id}/edit");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fnotes%2F{$note->id}%2Fedit");
    }

    public function testEditFailsIfNoteDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $content = $this->fake('sentence');
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $content,
        ]);

        $response = $this->appRun('GET', '/notes/not-an-id/edit');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserHasNoAccessToNote(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $content = $this->fake('sentence');
        $note = NoteFactory::create([
            'user_id' => $other_user->id,
            'content' => $content,
        ]);

        $response = $this->appRun('GET', "/notes/{$note->id}/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testUpdateChangesContentAndRedirect(): void
    {
        $user = $this->login();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
        $new_content = $this->fakeUnique('sentence');
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf_token' => $this->csrfToken(forms\notes\EditNote::class),
        ]);

        $this->assertResponseCode($response, 302, "/notes/{$note->id}/edit");
        $note = $note->reload();
        $this->assertSame($new_content, $note->content);
    }

    public function testUpdateChangesLinkTags(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'tags' => ['foo' => 'foo'],
        ]);
        $old_content = '#foo';
        $new_content = '#Bar';
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
            'content' => $old_content,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf_token' => $this->csrfToken(forms\notes\EditNote::class),
        ]);

        $this->assertResponseCode($response, 302, "/notes/{$note->id}/edit");
        $note = $note->reload();
        $this->assertSame($new_content, $note->content);
        $link = $link->reload();
        $this->assertEquals(['bar' => 'Bar'], $link->tags);
    }

    public function testUpdateRedirectsToLoginIfNotConnected(): void
    {
        $user = UserFactory::create();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
        $new_content = $this->fakeUnique('sentence');
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf_token' => $this->csrfToken(forms\notes\EditNote::class),
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fnotes%2F{$note->id}%2Fedit");
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testUpdateFailsIfNoteDoesNotExist(): void
    {
        $user = $this->login();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
        $new_content = $this->fakeUnique('sentence');
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->appRun('POST', '/notes/not-an-id/edit', [
            'content' => $new_content,
            'csrf_token' => $this->csrfToken(forms\notes\EditNote::class),
        ]);

        $this->assertResponseCode($response, 404);
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testUpdateFailsIfUserHasNoAccessToNote(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
        $new_content = $this->fakeUnique('sentence');
        $note = NoteFactory::create([
            'user_id' => $other_user->id,
            'content' => $old_content,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf_token' => $this->csrfToken(forms\notes\EditNote::class),
        ]);

        $this->assertResponseCode($response, 403);
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testUpdateFailsIfContentIsEmpty(): void
    {
        $user = $this->login();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        $new_content = '';
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf_token' => $this->csrfToken(forms\notes\EditNote::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The content is required');
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        /** @var string */
        $old_content = $this->fakeUnique('sentence');
        /** @var string */
        $new_content = $this->fakeUnique('sentence');
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $note = $note->reload();
        $this->assertSame($old_content, $note->content);
    }

    public function testDeleteDeletesNoteAndRedirects(): void
    {
        $user = $this->login();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\notes\DeleteNote::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Note::exists($note->id));
    }

    public function testDeleteChangesLinkTags(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'tags' => ['foo'],
        ]);
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'link_id' => $link->id,
            'content' => '#foo',
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\notes\DeleteNote::class),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Note::exists($note->id));
        $link = $link->reload();
        $this->assertEquals([], $link->tags);
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\notes\DeleteNote::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue(models\Note::exists($note->id));
    }

    public function testDeleteFailsIfNoteIsNotOwned(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $note = NoteFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\notes\DeleteNote::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue(models\Note::exists($note->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf_token' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertTrue(models\Note::exists($note->id));
        $error = \Minz\Flash::get('error');
        $this->assertTrue(is_string($error));
        $this->assertStringContainsString('A security verification failed', $error);
    }
}
