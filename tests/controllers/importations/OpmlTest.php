<?php

namespace flusio\controllers\importations;

use flusio\models;

// In PHP, this function verifies a file has been uploaded via HTTP POST. We
// want it for security reasons. Unfortunately, it prevents us to test the file
// upload because we can’t bypass it. One solution would be to create a wrapper
// around this function, but the cheapest solution is to redeclare it in the
// current namespace.
//
// If uploading files become a thing in flusio, I should consider a design less
// "hacky".
//
// @see https://www.php.net/manual/fr/function.is-uploaded-file.php
function move_uploaded_file($source_filepath, $destination_filepath)
{
    if ($source_filepath === 'not_uploaded_file') {
        return false;
    }

    return rename($source_filepath, $destination_filepath);
}

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \tests\LoginHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testShowRendersCorrectly()
    {
        $user = $this->login();

        $response = $this->appRun('get', '/opml');

        $this->assertResponse($response, 200, 'Importation from an OPML file');
        $this->assertPointer($response, 'importations/opml/show.phtml');
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

        $this->assertResponse($response, 200, 'We’re importing your data');
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

        $this->assertResponse($response, 200, 'We’ve imported your data from your <abbr>OPML</abbr> file.');
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

        $this->assertResponse($response, 200, $error);
    }

    public function testShowRedirectsToLoginIfNotConnected()
    {
        $response = $this->appRun('get', '/opml');

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fopml');
    }

    public function testImportRegistersAnOpmlImportatorJobAndRendersCorrectly()
    {
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
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

        $this->assertResponse($response, 200, 'Importation from an OPML file');
        $this->assertPointer($response, 'importations/opml/show.phtml');
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
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
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
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => 'a token',
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponse($response, 302, '/login?redirect_to=%2Fopml');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfAnImportAlreadyExists()
    {
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
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

        $this->assertResponse($response, 400, 'You already have an ongoing OPML importation');
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfCsrfIsInvalid()
    {
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
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

        $this->assertResponse($response, 400, 'A security verification failed');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfFileIsNotPassed()
    {
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
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

        $this->assertResponse($response, 400, 'The file is required');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testImportFailsIfFileIsTooLarge($error)
    {
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponse($response, 400, 'This file is too large');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    /**
     * @dataProvider otherFileErrorsProvider
     */
    public function testImportFailsIfFileFailedToUpload($error)
    {
        // we copy an existing file as a tmp file to simulate an upload
        $opml_filepath = \Minz\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.xml';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($opml_filepath, $tmp_filepath);

        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponse($response, 400, "This file cannot be uploaded (error {$error}).");
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, $job_dao->count());
    }

    public function testImportFailsIfMoveUploadedFileReturnsFalse()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
        $job_dao = new models\dao\Job();
        $user = $this->login();
        $file = [
            // see move_uploaded_file override at the top of the file: it will
            // return false if the tmp_name is 'not_uploaded_file'.
            'tmp_name' => 'not_uploaded_file',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('post', '/opml', [
            'csrf' => $user->csrf,
            'opml' => $file,
        ]);

        \Minz\Configuration::$application['job_adapter'] = 'test';

        $this->assertResponse($response, 400, 'This file cannot be uploaded.');
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
