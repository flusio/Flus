<?php

namespace flusio\cli;

use flusio\models;
use flusio\services;
use flusio\utils;

class MediaTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;
    use \tests\MockHttpHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    /**
     * @after
     */
    public function clearMediaDirectories()
    {
        $media_path = \Minz\Configuration::$application['media_path'];
        $file_names = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $media_path,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($file_names as $file_name) {
            if (is_dir($file_name)) {
                rmdir($file_name);
            } else {
                unlink($file_name);
            }
        }
    }

    public function testCleanDeletesUnusedFiles()
    {
        $image_service = new services\Image();
        $image_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFile($image_url, 'public/static/og-card.png');
        $image_filename = $image_service->generatePreviews($image_url);

        $this->assertNotEmpty($image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $card_filepath = "{$media_path}/cards/{$subpath}/{$image_filename}";
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $large_filepath = "{$media_path}/large/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));

        $response_generator = $this->appRun('cli', '/media/clean');

        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the media directories...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, " sub-directories found.");
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Searching for unused media files...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Deleted file {$image_filename}");
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '1 files deleted.');
        $this->assertFalse(file_exists($card_filepath));
        $this->assertFalse(file_exists($cover_filepath));
        $this->assertFalse(file_exists($large_filepath));
    }

    public function testCleanKeepsFilesUsedByLinks()
    {
        $image_service = new services\Image();
        $image_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFile($image_url, 'public/static/og-card.png');
        $image_filename = $image_service->generatePreviews($image_url);
        $this->create('link', [
            'image_filename' => $image_filename,
        ]);

        $this->assertNotEmpty($image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $card_filepath = "{$media_path}/cards/{$subpath}/{$image_filename}";
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $large_filepath = "{$media_path}/large/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));

        $response_generator = $this->appRun('cli', '/media/clean');

        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the media directories...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, " sub-directories found.");
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Searching for unused media files...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }

    public function testCleanKeepsFilesUsedByCollections()
    {
        $image_service = new services\Image();
        $image_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFile($image_url, 'public/static/og-card.png');
        $image_filename = $image_service->generatePreviews($image_url);
        $this->create('collection', [
            'image_filename' => $image_filename,
        ]);

        $this->assertNotEmpty($image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $card_filepath = "{$media_path}/cards/{$subpath}/{$image_filename}";
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $large_filepath = "{$media_path}/large/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));

        $response_generator = $this->appRun('cli', '/media/clean');

        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the media directories...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, " sub-directories found.");
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Searching for unused media files...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }

    public function testCleanKeepsFilesUsedByTopics()
    {
        $image_service = new services\Image();
        $image_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFile($image_url, 'public/static/og-card.png');
        $image_filename = $image_service->generatePreviews($image_url);
        $this->create('topic', [
            'image_filename' => $image_filename,
        ]);

        $this->assertNotEmpty($image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $card_filepath = "{$media_path}/cards/{$subpath}/{$image_filename}";
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $large_filepath = "{$media_path}/large/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));

        $response_generator = $this->appRun('cli', '/media/clean');

        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the media directories...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, " sub-directories found.");
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Searching for unused media files...');
        $response_generator->next();
        $response = $response_generator->current();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }
}
