<?php

namespace App\controllers;

use App\models;
use tests\factories\LinkFactory;
use tests\factories\NoteFactory;
use tests\factories\UserFactory;

class NotesTest extends \PHPUnit\Framework\TestCase
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
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', "/notes/{$note->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'notes/edit.phtml');
        $this->assertResponseContains($response, $content);
    }

    public function testEditRedirectsToLoginIfNotConnected(): void
    {
        /** @var string */
        $content = $this->fake('sentence');
        $note = NoteFactory::create([
            'content' => $content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', "/notes/{$note->id}/edit", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
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
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', '/notes/not-an-id/edit', [
            'from' => $from,
        ]);

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
        $from = \Minz\Url::for('home');

        $response = $this->appRun('GET', "/notes/{$note->id}/edit", [
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
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
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
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $note = $note->reload();
        $this->assertSame($new_content, $note->content);
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
        $note = NoteFactory::create([
            'user_id' => $user->id,
            'content' => $old_content,
        ]);
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf' => 'a token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
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
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', '/notes/not-an-id/edit', [
            'content' => $new_content,
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
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
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
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
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf' => \App\Csrf::generate(),
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertEquals([
            'content' => 'The content is required.',
        ], \Minz\Flash::get('errors'));
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
        $from = \Minz\Url::for('home');

        $response = $this->appRun('POST', "/notes/{$note->id}/edit", [
            'content' => $new_content,
            'csrf' => 'not the token',
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 302, $from);
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
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
            'csrf' => \App\Csrf::generate(),
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
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertFalse(models\Note::exists($note->id));
        $link = $link->reload();
        $this->assertEquals([], $link->tags);
    }

    public function testDeleteRedirectsToRedirectToIfGiven(): void
    {
        $user = $this->login();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf' => \App\Csrf::generate(),
            'redirect_to' => \Minz\Url::for('bookmarks'),
        ]);

        $this->assertResponseCode($response, 302, '/bookmarks');
        $this->assertFalse(models\Note::exists($note->id));
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf' => 'a token',
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
            'csrf' => \App\Csrf::generate(),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Note::exists($note->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $note = NoteFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/notes/{$note->id}/delete", [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 302, '/');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertTrue(models\Note::exists($note->id));
    }
}
