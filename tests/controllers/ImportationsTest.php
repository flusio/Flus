<?php

namespace flusio\controllers;

use flusio\models;

class ImportationsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\FlashAsserts;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testDeleteDeletesImportationAndRedirects()
    {
        $user = $this->login();
        $importation_id = $this->create('importation', [
            'user_id' => $user->id,
            'type' => 'pocket',
        ]);

        $response = $this->appRun('post', "/importations/{$importation_id}/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 302, '/links');
        $this->assertFalse(models\Importation::exists($importation_id));
    }

    public function testDeleteRedirectsIfNotConnected()
    {
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $importation_id = $this->create('importation', [
            'user_id' => $user_id,
        ]);

        $response = $this->appRun('post', "/importations/{$importation_id}/delete", [
            'csrf' => 'a token',
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fpocket');
        $this->assertTrue(models\Importation::exists($importation_id));
    }

    public function testDeleteFailsIfImportationIsNotOwned()
    {
        $user = $this->login();
        $other_user_id = $this->create('user');
        $importation_id = $this->create('importation', [
            'user_id' => $other_user_id,
        ]);

        $response = $this->appRun('post', "/importations/{$importation_id}/delete", [
            'csrf' => $user->csrf,
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertTrue(models\Importation::exists($importation_id));
    }

    public function testDeleteFailsIfCsrfIsInvalid()
    {
        $user = $this->login();
        $importation_id = $this->create('importation', [
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', "/importations/{$importation_id}/delete", [
            'csrf' => 'not the token',
            'from' => \Minz\Url::for('pocket'),
        ]);

        $this->assertResponseCode($response, 302, '/pocket');
        $this->assertFlash('error', 'A security verification failed.');
        $this->assertTrue(models\Importation::exists($importation_id));
    }
}
