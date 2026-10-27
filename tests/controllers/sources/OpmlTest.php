<?php

namespace App\controllers\sources;

use App\forms;
use App\jobs;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\ImportationFactory;
use tests\factories\StreamFactory;

class OpmlTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \Minz\Tests\TimeHelper;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testShowRendersCorrectly(): void
    {
        $this->freeze(new \DateTimeImmutable('2026-10-27'));
        $user = $this->login();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);
        /** @var string */
        $collection_name = $this->fake('words', 3, true);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'name' => $collection_name,
            'is_public' => true,
        ]);
        /** @var string */
        $feed_name = $this->fake('words', 3, true);
        $feed = CollectionFactory::create([
            'type' => 'feed',
            'name' => $feed_name,
            'is_public' => true,
        ]);
        $user->follow($collection);
        $user->follow($feed);
        $stream->addSource($feed);

        $response = $this->appRun('GET', '/sources/opml.xml');

        $this->assertResponseCode($response, 200);
        $this->assertResponseHeaders($response, [
            'Content-Type' => 'text/x-opml',
            'Content-Disposition' => 'inline; filename="2026-10-27_Flus_sources.opml.xml"',
        ]);
        $this->assertResponseContains($response, $collection_name);
        $this->assertResponseContains($response, $stream_name);
        $this->assertResponseContains($response, $feed_name);
    }

    public function testShowRedirectsIfNotConnected(): void
    {
        $redirect_to = urlencode('/sources/opml.xml');

        $response = $this->appRun('GET', '/sources/opml.xml');

        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/sources');
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

        $response = $this->appRun('POST', '/sources/opml', [
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/login?redirect_to=%2F');
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/sources');
        $this->assertSame('You already have an ongoing OPML importation.', utils\Notification::popError());
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => 'not the token',
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/sources');
        $this->assertStringContainsString('A security verification failed', utils\Notification::popError());
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/sources');
        $this->assertStringContainsString('The file is required', utils\Notification::popError());
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/sources');
        $this->assertStringContainsString('This file is too large', utils\Notification::popError());
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/sources');
        $this->assertStringContainsString(
            "This file cannot be uploaded (error {$error}).",
            utils\Notification::popError(),
        );
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

        $response = $this->appRun('POST', '/sources/opml', [
            'csrf_token' => $this->csrfToken(forms\importations\OpmlImportation::class),
            'opml' => $file,
        ]);

        \App\Configuration::$jobs_adapter = 'test';

        $this->assertResponseCode($response, 302, '/sources');
        $this->assertStringContainsString('This file cannot be uploaded (error -1).', utils\Notification::popError());
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

    public function testAliasRedirectsToSources(): void
    {
        $response = $this->appRun('GET', '/opml');

        $this->assertResponseCode($response, 301, '/sources');
    }
}
