<?php

namespace flusio\controllers\importations;

use flusio\jobs;
use flusio\models;
use tests\factories\ImportationFactory;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Importation from an OPML file');
        $this->assertResponsePointer($response, 'importations/opml/show.phtml');
    }

    public function testShowRendersIfImportationIsOngoing()
    {
        $user = $this->login();
        ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’re importing your data');
    }

    public function testShowRendersIfImportationIsFinished()
    {
        $user = $this->login();
        ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'finished',
        ]);

        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’ve imported your data from your <abbr>OPML</abbr> file.');
    }

    public function testShowRendersIfImportationIsInError()
    {
        $user = $this->login();
        $error = $this->fake('sentence');
        ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'error',
            'error' => $error,
        ]);

        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $error);
    }

    public function testShowRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fopml');
    }

    public function testImportRegistersAnOpmlImportatorJobAndRedirects()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/opml');
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(1, \Minz\Job::count());
        $importation = models\Importation::take();
        $job = \Minz\Job::take();
        $this->assertSame(jobs\OpmlImportator::class, $job->name);
        $this->assertSame([$importation->id], $job->args);
    }

    public function testImportCopiesFileUnderData()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $destination_filepath = \Minz\Configuration::$data_path . "/importations/opml_{$user->id}.xml";
        $this->assertTrue(file_exists($destination_filepath));
    }

    public function testImportRedirectsToLoginIfNotConnected()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf' => 'a token',
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fopml');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfAnImportAlreadyExists()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You already have an ongoing OPML importation');
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfCsrfIsInvalid()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/opml', [
            'csrf' => 'not the token',
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfFileIsNotPassed()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/opml', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The file is required');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testImportFailsIfFileIsTooLarge($error)
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file is too large');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    /**
     * @dataProvider otherFileErrorsProvider
     */
    public function testImportFailsIfFileFailedToUpload($error)
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "This file cannot be uploaded (error {$error}).");
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfIsUploadedFileReturnsFalse()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file cannot be uploaded (error -1).');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function tooLargeErrorsProvider()
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
        ];
    }

    public function otherFileErrorsProvider()
    {
        return [
            [UPLOAD_ERR_PARTIAL],
            [UPLOAD_ERR_NO_FILE],
            [UPLOAD_ERR_NO_TMP_DIR],
            [UPLOAD_ERR_CANT_WRITE],
            [UPLOAD_ERR_EXTENSION],
        ];
    }
}
