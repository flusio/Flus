<?php

namespace App\controllers\streams;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;
use tests\factories\ViewFactory;

class ViewsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testNewRendersCorrectly(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/new");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/views/new.html.twig');
        $this->assertResponseContains($response, 'New view');
    }

    public function testNewRendersTheFiltersAsHiddenInputs(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/new", [
            'status' => 'unread',
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'value="unread"');
    }

    public function testNewRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/new");

        $redirect_to = urlencode("/streams/{$stream->id}/views/new");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testNewFailsIfStreamDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/streams/unknown/views/new');

        $this->assertResponseCode($response, 404);
    }

    public function testNewFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/new");

        $this->assertResponseCode($response, 403);
    }

    public function testCreateCreatesTheViewAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $at = \Minz\Time::relative('-3 days');

        $this->assertSame(0, models\View::count());

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My view',
            'at' => $at->format('Y-m-d'),
            'days' => '7',
            'source' => '',
            'status' => 'unread',
            'with_dismissed' => '1',
            'q' => 'foo',
        ]);

        $this->assertSame(1, models\View::count());
        $view = models\View::take();
        $this->assertNotNull($view);
        $this->assertResponseCode($response, 302, "/streams/{$stream->id}?view={$view->id}");
        $this->assertSame('My view', $view->name);
        $this->assertSame($stream->id, $view->stream_id);
        $this->assertSame($user->id, $view->user_id);
        $this->assertFalse($view->is_default);
        $this->assertSame([
            'at_offset' => '-3',
            'days' => '7',
            'source' => '',
            'status' => 'unread',
            'with_dismissed' => '1',
            'q' => 'foo',
        ], $view->parameters);
    }

    public function testCreateNormalizesTheParameters(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $not_a_source = CollectionFactory::create();

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My view',
            'at' => \Minz\Time::relative('-3 days')->format('Y-m-d'),
            'days' => '99',
            'source' => $not_a_source->id,
            'status' => 'bogus',
            'with_dismissed' => '',
            'q' => '',
        ]);

        $view = models\View::take();
        $this->assertNotNull($view);
        $this->assertSame([
            'at_offset' => '-3',
            'days' => '7',
            'source' => '',
            'status' => 'all',
            'with_dismissed' => '',
            'q' => '',
        ], $view->parameters);
    }

    public function testCreateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My view',
        ]);

        $redirect_to = urlencode("/streams/{$stream->id}/views/new");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
        $this->assertSame(0, models\View::count());
    }

    public function testCreateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => 'not the token',
            'name' => 'My view',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\View::count());
    }

    public function testCreateFailsIfNameIsMissing(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name is required');
        $this->assertSame(0, models\View::count());
    }

    public function testCreateFailsIfNameIsTooLong(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => str_repeat('a', models\View::NAME_MAX_LENGTH + 1),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The name must be less than');
        $this->assertSame(0, models\View::count());
    }

    public function testCreateFailsIfNameIsAlreadyUsed(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My view',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My view',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A view with this name already exists');
        $this->assertSame(1, models\View::count());
    }

    public function testCreateFailsIfStreamDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('POST', '/streams/unknown/views/new', [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My view',
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testCreateFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/new", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My view',
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(0, models\View::count());
    }

    public function testSaveSavesTheParametersAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'parameters' => models\View::STREAM_PARAMETERS,
        ]);
        $referer = "/streams/{$stream->id}";

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'status' => 'unread',
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $view = $view->reload();
        $this->assertSame([
            'at_offset' => '0',
            'days' => '1',
            'source' => '',
            'status' => 'unread',
            'with_dismissed' => '',
            'q' => '',
        ], $view->parameters);
    }

    public function testSaveCreatesTheDefaultViewIfNotPersisted(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, models\View::count());

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/default/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'status' => 'unread',
        ]);

        $this->assertSame(1, models\View::count());
        $view = models\View::take();
        $this->assertNotNull($view);
        $this->assertTrue($view->is_default);
        $this->assertSame('Main view', $view->name);
        $this->assertSame($stream->id, $view->stream_id);
        $this->assertSame($user->id, $view->user_id);
        $this->assertSame('unread', $view->parameters['status']);
    }

    public function testSaveFailsIfDefaultViewNameIsAlreadyUsed(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        // The view could be created with this name because the default view
        // had no row in database yet.
        ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'Main view',
        ]);
        $referer = "/streams/{$stream->id}";

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/default/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'status' => 'unread',
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $this->assertStringContainsString(
            'A view with this name already exists',
            utils\Notification::popError(),
        );
        $this->assertSame(1, models\View::count());
    }

    public function testSaveNormalizesTheParameters(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'parameters' => models\View::STREAM_PARAMETERS,
        ]);
        $not_a_source = CollectionFactory::create();

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'at' => \Minz\Time::relative('-3 days')->format('Y-m-d'),
            'days' => '99',
            'source' => $not_a_source->id,
            'status' => 'bogus',
        ]);

        $view = $view->reload();
        $this->assertSame([
            'at_offset' => '-3',
            'days' => '7',
            'source' => '',
            'status' => 'all',
            'with_dismissed' => '',
            'q' => '',
        ], $view->parameters);
    }

    public function testSaveRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'status' => 'unread',
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
    }

    public function testSaveFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'parameters' => models\View::STREAM_PARAMETERS,
        ]);
        $referer = "/streams/{$stream->id}";

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/save", [
            'csrf_token' => 'not the token',
            'status' => 'unread',
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $this->assertStringContainsString('A security verification failed', utils\Notification::popError());
        $view = $view->reload();
        $this->assertSame('all', $view->parameters['status']);
    }

    public function testSaveFailsIfViewDoesNotExist(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/unknown/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'status' => 'unread',
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testSaveFailsIfViewIsAttachedToAnotherStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $other_stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $other_stream->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'status' => 'unread',
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testSaveFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/save", [
            'csrf_token' => $this->csrfToken(forms\views\SaveView::class),
            'status' => 'unread',
        ]);

        $this->assertResponseCode($response, 403);
    }

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My view',
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/{$view->id}/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/views/edit.html.twig');
        $this->assertResponseContains($response, 'Rename the view');
        $this->assertResponseContains($response, 'My view');
    }

    public function testEditRendersTheDefaultViewEvenIfNotPersisted(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/default/edit");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Main view');
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/{$view->id}/edit");

        $redirect_to = urlencode("/streams/{$stream->id}/views/{$view->id}/edit");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testEditFailsIfViewIsAttachedToAnotherStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $other_stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $other_stream->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/{$view->id}/edit");

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/views/{$view->id}/edit");

        $this->assertResponseCode($response, 403);
    }

    public function testUpdateRenamesTheViewAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My view',
        ]);
        $referer = "/streams/{$stream->id}?view={$view->id}";

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'Renamed view',
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $view = $view->reload();
        $this->assertSame('Renamed view', $view->name);
    }

    public function testUpdateCreatesTheDefaultViewIfNotPersisted(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $this->assertSame(0, models\View::count());

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/default/edit", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My main view',
        ]);

        $this->assertSame(1, models\View::count());
        $view = models\View::take();
        $this->assertNotNull($view);
        $this->assertTrue($view->is_default);
        $this->assertSame('My main view', $view->name);
        $this->assertSame(models\View::STREAM_PARAMETERS, $view->parameters);
    }

    public function testUpdateFailsIfNameIsAlreadyUsed(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My view',
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My other view',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'My view',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A view with this name already exists');
        $view = $view->reload();
        $this->assertSame('My other view', $view->name);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My view',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'Renamed view',
        ]);

        $redirect_to = urlencode("/streams/{$stream->id}/views/{$view->id}/edit");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
        $view = $view->reload();
        $this->assertSame('My view', $view->name);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My view',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/edit", [
            'csrf_token' => 'not the token',
            'name' => 'Renamed view',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $view = $view->reload();
        $this->assertSame('My view', $view->name);
    }

    public function testUpdateFailsIfViewIsAttachedToAnotherStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $other_stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $other_stream->id,
            'name' => 'My view',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'Renamed view',
        ]);

        $this->assertResponseCode($response, 404);
        $view = $view->reload();
        $this->assertSame('My view', $view->name);
    }

    public function testUpdateFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'name' => 'My view',
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/edit", [
            'csrf_token' => $this->csrfToken(forms\views\View::class),
            'name' => 'Renamed view',
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame('My view', $view->name);
    }

    public function testDeleteDeletesTheViewAndRedirects(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\views\DeleteView::class),
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}");
        $this->assertSame(0, models\View::count());
        $this->assertStringContainsString('The view has been deleted', utils\Notification::popSuccess());
    }

    public function testDeleteResetsTheDefaultView(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
            'is_default' => true,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\views\DeleteView::class),
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}");
        $this->assertSame(0, models\View::count());
        $this->assertStringContainsString('The default view has been reset', utils\Notification::popSuccess());
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\views\DeleteView::class),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertSame(1, models\View::count());
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);
        $referer = "/streams/{$stream->id}";

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/delete", [
            'csrf_token' => 'not the token',
        ], headers: [
            'Referer' => $referer,
        ]);

        $this->assertResponseCode($response, 302, $referer);
        $this->assertStringContainsString('A security verification failed', utils\Notification::popError());
        $this->assertSame(1, models\View::count());
    }

    public function testDeleteFailsIfViewIsAttachedToAnotherStream(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $other_stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $other_stream->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\views\DeleteView::class),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertSame(1, models\View::count());
    }

    public function testDeleteFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);
        $view = ViewFactory::create([
            'stream_id' => $stream->id,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/views/{$view->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\views\DeleteView::class),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertSame(1, models\View::count());
    }
}
