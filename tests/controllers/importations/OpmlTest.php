<?php

namespace App\controllers\importations;

use App\forms;
use App\jobs;
use App\models;
use tests\factories\ImportationFactory;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        $user = $this->login();

        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'Importation from an OPML file');
        $this->assertResponseTemplateName($response, 'importations/opml/show.html.twig');
    }

    public function testShowRendersIfImportationIsOngoing(): void
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

    public function testShowRendersIfImportationIsFinished(): void
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

    public function testShowRendersIfImportationIsInError(): void
    {
        $user = $this->login();
        /** @var string */
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

    public function testShowRedirectsToLoginIfNotConnected(): void
    {
        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fopml');
    }

    public function testImportRegistersAnOpmlImportatorJobAndRedirects(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/opml');
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(1, \Minz\Job::count());
        $importation = models\Importation::take();
        $this->assertNotNull($importation);
        $job = \Minz\Job::take();
        $this->assertNotNull($job);
        $this->assertSame(jobs\OpmlImportator::class, $job->name);
        $this->assertSame([$importation->id], $job->args);
    }

    public function testImportCopiesFileUnderData(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $destination_filepath = \App\Configuration::$data_path . "/importations/opml_{$user->id}.xml";
        $this->assertTrue(file_exists($destination_filepath));
    }

    public function testImportRedirectsToLoginIfNotConnected(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2Fopml');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfAnImportAlreadyExists(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        ImportationFactory::create([
            'type' => 'opml',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertSame(1, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfCsrfIsInvalid(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => 'not the token',
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfFileIsNotPassed(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];

        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The file is required');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tooLargeErrorsProvider')]
    public function testImportFailsIfFileIsTooLarge(int $error): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file is too large');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('otherFileErrorsProvider')]
    public function testImportFailsIfFileFailedToUpload(int $error): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'name' => 'freshrss.opml.xml',
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "This file cannot be uploaded (error {$error}).");
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    public function testImportFailsIfIsUploadedFileReturnsFalse(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
        $user = $this->login();
        $opml_filepath = \App\Configuration::$app_path . '/tests/lib/SpiderBits/examples/freshrss.opml.xml';
        $tmp_filepath = $this->tmpCopyFile($opml_filepath);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];

        $response = $this->appRun('POST', '/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'This file cannot be uploaded (error -1).');
        $this->assertSame(0, models\Importation::count());
        $this->assertSame(0, \Minz\Job::count());
    }

    /**
     * @return array<array{int}>
     */
    public static function tooLargeErrorsProvider(): array
    {
        return [
            [UPLOAD_ERR_INI_SIZE],
            [UPLOAD_ERR_FORM_SIZE],
        ];
    }

    /**
     * @return array<array{int}>
     */
    public static function otherFileErrorsProvider(): array
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
