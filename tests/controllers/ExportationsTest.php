<?php

namespace flusio\controllers;

use flusio\models;
use flusio\services;

class ExportationsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function setJobAdapterToDatabase()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
    }

    /**
     * @afterClass
     */
    public static function setJobAdapterToTest()
    {
        \Minz\Configuration::$application['job_adapter'] = 'test';
    }

    public function testShowRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, 'Generate a data archive');
    }

    public function testShowRendersCorrectlyIfAnExportationIsOngoing()
    {
        $user = $this->login();
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $response = $this->appRun('get', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, 'We’re creating your archive');
    }

    public function testShowRendersCorrectlyIfAnExportationIsFinished()
    {
        $user = $this->login();
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'finished',
        ]);

        $response = $this->appRun('get', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, 'Downloading your data');
    }

    public function testShowRendersCorrectlyIfAnExportationIsError()
    {
        $user = $this->login();
        $error = $this->fake('sentence');
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'error',
            'error' => $error,
        ]);

        $response = $this->appRun('get', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, $error);
    }

    public function testShowRedirectsIfUserIsNotConnected()
    {
        $response = $this->appRun('get', '/exportations');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fexportations');
    }

    public function testCreateCreatesAnExportationAndRedirects()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();

        $this->assertSame(0, models\Exportation::count());
        $this->assertSame(0, $job_dao->count());

        $response = $this->appRun('post', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/exportations');
        $this->assertSame(1, models\Exportation::count());
        $exportation = models\Exportation::take();
        $this->assertSame($user->id, $exportation->user_id);
        $this->assertSame('ongoing', $exportation->status);
        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->listAll()[0];
        $handler = json_decode($db_job['handler'], true);
        $this->assertSame('flusio\\jobs\\Exportator', $handler['job_class']);
        $this->assertSame([$exportation->id], $handler['job_args']);
    }

    public function testCreateDeletesExistingExportationAndArchiveIfStatusIsFinished()
    {
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);

        $this->assertSame(1, models\Exportation::count());
        $this->assertSame(0, $job_dao->count());
        $this->assertTrue(file_exists($filepath));

        $response = $this->appRun('post', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/exportations');
        $this->assertSame(1, models\Exportation::count());
        $exportation = models\Exportation::take();
        $this->assertNotSame($exportation_id, $exportation->id);
        $this->assertSame(1, $job_dao->count());
        $this->assertFalse(file_exists($filepath));
    }

    public function testCreateDeletesExistingExportationIfStatusIsError()
    {
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'error',
        ]);

        $this->assertSame(1, models\Exportation::count());
        $this->assertSame(0, $job_dao->count());

        $response = $this->appRun('post', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/exportations');
        $this->assertSame(1, models\Exportation::count());
        $exportation = models\Exportation::take();
        $this->assertNotSame($exportation_id, $exportation->id);
        $this->assertSame(1, $job_dao->count());
    }

    public function testCreateRedirectsIfUserIsNotConnected()
    {
        $job_dao = new models\dao\Job();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);

        $response = $this->appRun('post', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fexportations');
        $this->assertSame(0, models\Exportation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testCreateFailsIfAnExportationStatusIsOngoing()
    {
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $this->assertSame(1, models\Exportation::count());
        $this->assertSame(0, $job_dao->count()); // in real life, a job should exist

        $response = $this->appRun('post', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You already have an ongoing exportation');
        $this->assertSame(1, models\Exportation::count());
        $exportation = models\Exportation::take();
        $this->assertSame($exportation_id, $exportation->id);
        $this->assertSame(0, $job_dao->count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $job_dao = new models\dao\Job();
        $user = $this->login();

        $response = $this->appRun('post', '/exportations', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Exportation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testDownloadReturnsTheArchiveFile()
    {
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);

        $response = $this->appRun('get', '/exportations/download');

        $this->assertResponseCode($response, 200);
        $exportation = models\Exportation::find($exportation_id);
        $filename_date = $exportation->created_at->format('Y-m-d');
        $filename_brand = \Minz\Configuration::$application['brand'];
        $filename = "{$filename_date}_{$filename_brand}_data.zip";
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'application/zip',
            'Content-Length' => filesize($filepath),
            'Content-Disposition' => "filename={$filename}",
        ]);
    }

    public function testDownloadRedirectsIfUserIsNotConnected()
    {
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);

        $response = $this->appRun('get', '/exportations/download');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fexportations');
    }

    public function testDownloadFailsIfFileDoesNotExist()
    {
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);
        unlink($filepath);

        $response = $this->appRun('get', '/exportations/download');

        $this->assertResponseCode($response, 404);
    }

    public function testDownloadFailsIfExportationDoesNotExist()
    {
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);

        $response = $this->appRun('get', '/exportations/download');

        $this->assertResponseCode($response, 404);
    }
}
