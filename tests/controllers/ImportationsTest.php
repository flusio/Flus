<?php

namespace App\controllers;

use App\models;
use tests\factories\ImportationFactory;
use tests\factories\UserFactory;

class ImportationsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testDeleteDeletesImportationAndRedirects(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
            'type' => 'pocket',
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 302, '/links');
        $this->assertFalse(models\Importation::exists($importation->id));
    }

    public function testDeleteRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create([
            'csrf' => 'a token',
        ]);
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf' => 'a token',
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fpocket');
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
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Importation::exists($importation->id));
    }

    public function testDeleteFailsIfCsrfIsInvalid(): void
    {
        $user = $this->login();
        $importation = ImportationFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', "/importations/{$importation->id}/delete", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 302, '/pocket');
        $this->assertSame('A security verification failed.', \Minz\Flash::get('error'));
        $this->assertTrue(models\Importation::exists($importation->id));
    }
}
