<?php

namespace flusio\controllers;

use flusio\jobs;
use flusio\models;
use flusio\services;
use tests\factories\ExportationFactory;
use tests\factories\UserFactory;

class ExportationsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function setJobAdapterToDatabase()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
    }

    /**
     * @afterClass
     */
    public static function setJobAdapterToTest()
    {
        \Minz\Configuration::$jobs_adapter = 'test';
    }

    public function testShowRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, 'Generate a data archive');
    }

    public function testShowRendersCorrectlyIfAnExportationIsOngoing()
    {
        $user = $this->login();
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $response = $this->appRun('GET', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, 'We’re creating your archive');
    }

    public function testShowRendersCorrectlyIfAnExportationIsFinished()
    {
        $user = $this->login();
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'finished',
        ]);

        $response = $this->appRun('GET', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, 'Downloading your data');
    }

    public function testShowRendersCorrectlyIfAnExportationIsError()
    {
        $user = $this->login();
        $error = $this->fake('sentence');
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'error',
            'error' => $error,
        ]);

        $response = $this->appRun('GET', '/exportations');

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'exportations/show.phtml');
        $this->assertResponseContains($response, $error);
    }

    public function testShowRedirectsIfUserIsNotConnected()
    {
        $response = $this->appRun('GET', '/exportations');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fexportations');
    }

    public function testCreateCreatesAnExportationAndRedirects()
    {
        $user = $this->login();

        $this->assertSame(0, models\Exportation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/exportations');
        $this->assertSame(1, models\Exportation::count());
        $exportation = models\Exportation::take();
        $this->assertSame($user->id, $exportation->user_id);
        $this->assertSame('ongoing', $exportation->status);
        $this->assertSame(1, \Minz\Job::count());
        $job = \Minz\Job::take();
        $this->assertSame(jobs\Exportator::class, $job->name);
        $this->assertSame([$exportation->id], $job->args);
    }

    public function testCreateDeletesExistingExportationAndArchiveIfStatusIsFinished()
    {
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);

        $this->assertSame(1, models\Exportation::count());
        $this->assertSame(0, \Minz\Job::count());
        $this->assertTrue(file_exists($filepath));

        $response = $this->appRun('POST', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/exportations');
        $this->assertSame(1, models\Exportation::count());
        $this->assertFalse(models\Exportation::exists($exportation->id));
        $this->assertSame(1, \Minz\Job::count());
        $this->assertFalse(file_exists($filepath));
    }

    public function testCreateDeletesExistingExportationIfStatusIsError()
    {
        $user = $this->login();
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'error',
        ]);

        $this->assertSame(1, models\Exportation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/exportations');
        $this->assertSame(1, models\Exportation::count());
        $this->assertFalse(models\Exportation::exists($exportation->id));
        $this->assertSame(1, \Minz\Job::count());
    }

    public function testCreateRedirectsIfUserIsNotConnected()
    {
        $user = UserFactory::create();

        $response = $this->appRun('POST', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fexportations');
        $this->assertSame(0, models\Exportation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testCreateFailsIfAnExportationStatusIsOngoing()
    {
        $user = $this->login();
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $this->assertSame(1, models\Exportation::count());
        $this->assertSame(0, \Minz\Job::count()); // in real life, a job should exist

        $response = $this->appRun('POST', '/exportations', [
            'csrf' => $user->csrf,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You already have an ongoing exportation');
        $this->assertSame(1, models\Exportation::count());
        $this->assertTrue(models\Exportation::exists($exportation->id));
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testCreateFailsIfCsrfIsInvalid()
    {
        $user = $this->login();

        $response = $this->appRun('POST', '/exportations', [
            'csrf' => 'not the token',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Exportation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testDownloadReturnsTheArchiveFile()
    {
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);

        $response = $this->appRun('GET', '/exportations/download');

        $this->assertResponseCode($response, 200);
        $exportation = $exportation->reload();
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
        $user = UserFactory::create();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);

        $response = $this->appRun('GET', '/exportations/download');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fexportations');
    }

    public function testDownloadFailsIfFileDoesNotExist()
    {
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => $filepath,
        ]);
        unlink($filepath);

        $response = $this->appRun('GET', '/exportations/download');

        $this->assertResponseCode($response, 404);
    }

    public function testDownloadFailsIfExportationDoesNotExist()
    {
        $user = $this->login();
        $exportation_service = new services\DataExporter(\Minz\Configuration::$tmp_path);
        $filepath = $exportation_service->export($user->id);

        $response = $this->appRun('GET', '/exportations/download');

        $this->assertResponseCode($response, 404);
    }
}
