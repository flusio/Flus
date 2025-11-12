<?php

namespace App\controllers;

use App\forms;
use App\models;
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

    public function testDeleteDeletesImportationAndRedirects(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf_token' => $this->csrfToken(forms\DeleteImportation::class),
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
            'csrf_token' => $this->csrfToken(forms\DeleteImportation::class),
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
            'csrf_token' => $this->csrfToken(forms\DeleteImportation::class),
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
        $error = \Minz\Flash::get('error');
        $this->assertTrue(is_string($error));
        $this->assertStringContainsString('A security verification failed', $error);
        $this->assertTrue(models\Importation::exists($importation->id));
    }
}
