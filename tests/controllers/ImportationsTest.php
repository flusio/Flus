<?php

namespace App\controllers;

use App\forms;
use App\models;
use App\utils;
use tests\factories\ImportationFactory;
use tests\factories\UserFactory;

class ImportationsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersIfImportationIsOngoing(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $response = $this->appRun('GET', "/importations/{$importation->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'importations/show.html.twig');
        $this->assertResponseContains($response, 'We’re importing your OPML file');
    }

    public function testShowRendersIfImportationIsFinished(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'finished',
        ]);

        $response = $this->appRun('GET', "/importations/{$importation->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’ve imported your OPML file.');
    }

    public function testShowRendersIfImportationIsInError(): void
    {
        $user = $this->login();
        /** @var string */
        $error = $this->fake('sentence');
        $importation = ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'error',
            'error' => $error,
        ]);

        $response = $this->appRun('GET', "/importations/{$importation->id}");

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $error);
    }

    public function testShowRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
        ]);
        $redirect_to = urlencode("/importations/{$importation->id}");

        $response = $this->appRun('GET', "/importations/{$importation->id}");

        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testShowFailsIfTheImportationIsNotOwned(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $importation = ImportationFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/importations/{$importation->id}");

        $this->assertResponseCode($response, 403);
    }

    public function testDeleteDeletesImportationAndRedirects(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\importations\DeleteImportation::class),
            'redirect_to' => '/links',
        ]);

        $this->assertResponseCode($response, 302, '/links');
        $this->assertFalse(models\Importation::exists($importation->id));
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\importations\DeleteImportation::class),
            'redirect_to' => \Minz\Url::for('links'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
        $this->assertTrue(models\Importation::exists($importation->id));
    }

    public function testDeleteFailsIfImportationIsNotOwned(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $importation = ImportationFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\importations\DeleteImportation::class),
            'redirect_to' => \Minz\Url::for('links'),
        ]);

        $this->assertResponseCode($response, 403);
        $this->assertTrue(models\Importation::exists($importation->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf_token' => 'not the token',
            'redirect_to' => \Minz\Url::for('links'),
        ]);

        $this->assertResponseCode($response, 302, '/');
        $error = utils\Notification::popError();
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertTrue(models\Importation::exists($importation->id));
    }
}
