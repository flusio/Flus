<?php

namespace App\cli;

use App\services;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\LinkFactory;
use tests\factories\TopicFactory;

class MediaTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\HttpHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    #[\PHPUnit\Framework\Attributes\After]
    public function clearMediaDirectories(): void
    {
        $media_path = \App\Configuration::$application['media_path'];
        $file_names = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $media_path,
                \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::CURRENT_AS_PATHNAME
            ),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($file_names as $file_name) {
            assert(is_string($file_name));

            if (is_dir($file_name)) {
                rmdir($file_name);
            } else {
                unlink($file_name);
            }
        }
    }

    public function testCleanDeletesUnusedFiles(): void
    {
        $image_service = new services\Image();
        $image_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFile($image_url, 'public/static/og-card.png');
        $image_filename = $image_service->generatePreviews($image_url);
        $subdir_name = substr($image_filename, 0, 3);

        $this->assertNotEmpty($image_filename);
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($cover_filepath));

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the media directories...');
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, " sub-directories found.");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under {$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Deleted file {$image_filename}");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '1 files deleted.');
        $this->assertFalse(file_exists($cover_filepath));
        $this->assertFalse(file_exists(dirname($cover_filepath)));
    }

    public function testCleanKeepsFilesUsedByLinks(): void
    {
        $image_service = new services\Image();
        $image_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFile($image_url, 'public/static/og-card.png');
        $image_filename = $image_service->generatePreviews($image_url);
        $subdir_name = substr($image_filename, 0, 3);
        LinkFactory::create([
            'image_filename' => $image_filename,
        ]);

        $this->assertNotEmpty($image_filename);
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($cover_filepath));

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the media directories...');
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, " sub-directories found.");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under {$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Nothing to delete under {$subdir_name}/.");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($cover_filepath));
    }

    public function testCleanKeepsFilesUsedByCollections(): void
    {
        $image_service = new services\Image();
        $image_url = 'https://flus.fr/carnet/card.png';
        $this->mockHttpWithFile($image_url, 'public/static/og-card.png');
        $image_filename = $image_service->generatePreviews($image_url);
        $subdir_name = substr($image_filename, 0, 3);
        CollectionFactory::create([
            'image_filename' => $image_filename,
        ]);

        $this->assertNotEmpty($image_filename);
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($cover_filepath));

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the media directories...');
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, " sub-directories found.");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under {$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Nothing to delete under {$subdir_name}/.");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($cover_filepath));
    }
}
