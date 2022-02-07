<?php

namespace flusio\controllers\importations;

use flusio\models;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\InitializerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Importation from an OPML file');
        $this->assertResponsePointer($response, 'importations/opml/show.phtml');
    }

    public function testShowRendersIfImportationIsOngoing()
    {
        $user = $this->login();
        $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $response = $this->appRun('get', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’re importing your data');
    }

    public function testShowRendersIfImportationIsFinished()
    {
        $user = $this->login();
        $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'finished',
        ]);

        $response = $this->appRun('get', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'We’ve imported your data from your <abbr>OPML</abbr> file.');
    }

    public function testShowRendersIfImportationIsInError()
    {
        $user = $this->login();
        $error = $this->fake('sentence');
        $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
            'status' => 'error',
            'error' => $error,
        ]);

        $response = $this->appRun('get', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $error);
    }

    public function testShowRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('get', '/opml');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fopml');
    }

    public function testImportRegistersAnOpmlImportatorJobAndRedirects()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 302, '/opml');
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(1, $job_dao->count());
        $importation = models\Importation::take();
        $db_job = $job_dao->listAll()[0];
        $handler = json_decode($db_job['handler'], true);
        $this->assertSame('flusio\\jobs\\OpmlImportator', $handler['job_class']);
        $this->assertSame([$importation->id], $handler['job_args']);
    }

    public function testImportCopiesFileUnderData()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $destination_filepath = \Minz\Configuration::$data_path . "/importations/opml_{$user->id}.xml";
        $this->assertTrue(file_exists($destination_filepath));
    }

    public function testImportRedirectsToLoginIfNotConnected()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => 'a token',
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fopml');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfAnImportAlreadyExists()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        $this->create('importation', [
            'type' => 'opml',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'You already have an ongoing OPML importation');
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfCsrfIsInvalid()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());

        $response = $this->appRun('post', '/opml', [
            'csrf' => 'not the token',
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfFileIsNotPassed()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The file is required');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testImportFailsIfFileIsTooLarge($error)
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file is too large');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    /**
     * @dataProvider otherFileErrorsProvider
     */
    public function testImportFailsIfFileFailedToUpload($error)
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "This file cannot be uploaded (error {$error}).");
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfIsUploadedFileReturnsFalse()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file cannot be uploaded (error -1).');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
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
