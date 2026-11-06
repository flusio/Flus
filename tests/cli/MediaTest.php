<?php

namespace App\cli;

use App\images;
use tests\factories\CollectionFactory;
use tests\factories\FeedFactory;
use tests\factories\LinkFactory;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

/**
 * @phpstan-import-type MediaType from images\Storage
 */
class MediaTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FilesystemHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    public function testCleanDeletesUnusedFiles(): void
    {
        $image_filename = $this->createMediaFile('covers');
        $cover_filepath = images\Storage::filepath('covers', $image_filename);
        $subdir_name = substr($image_filename, 0, 3);

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the covers directory...');
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '1 sub-directories found.');
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under covers/{$subdir_name}/... (0%)");
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
        $image_filename = $this->createMediaFile('covers');
        $cover_filepath = images\Storage::filepath('covers', $image_filename);
        $subdir_name = substr($image_filename, 0, 3);
        LinkFactory::create([
            'image_filename' => $image_filename,
        ]);

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the covers directory...');
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under covers/{$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Nothing to delete under covers/{$subdir_name}/.");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($cover_filepath));
    }

    public function testCleanKeepsFilesUsedByCollections(): void
    {
        $image_filename = $this->createMediaFile('covers');
        $cover_filepath = images\Storage::filepath('covers', $image_filename);
        $subdir_name = substr($image_filename, 0, 3);
        CollectionFactory::create([
            'image_filename' => $image_filename,
        ]);

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the covers directory...');
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under covers/{$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Nothing to delete under covers/{$subdir_name}/.");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($cover_filepath));
    }

    public function testCleanKeepsFilesUsedByStreams(): void
    {
        $image_filename = $this->createMediaFile('covers');
        $cover_filepath = images\Storage::filepath('covers', $image_filename);
        $subdir_name = substr($image_filename, 0, 3);
        StreamFactory::create([
            'image_filename' => $image_filename,
        ]);

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the covers directory...');
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under covers/{$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Nothing to delete under covers/{$subdir_name}/.");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($cover_filepath));
    }

    public function testCleanKeepsFilesUsedByFollows(): void
    {
        $image_filename = $this->createMediaFile('covers');
        $cover_filepath = images\Storage::filepath('covers', $image_filename);
        $subdir_name = substr($image_filename, 0, 3);
        $user = UserFactory::create();
        $feed = FeedFactory::create();
        $follow = $user->follow($feed);
        $follow->image_filename = $image_filename;
        $follow->save();

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the covers directory...');
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under covers/{$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Nothing to delete under covers/{$subdir_name}/.");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($cover_filepath));
    }

    public function testCleanDeletesUnusedAvatars(): void
    {
        $avatar_filename = $this->createMediaFile('avatars');
        $avatar_filepath = images\Storage::filepath('avatars', $avatar_filename);
        $subdir_name = substr($avatar_filename, 0, 3);

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the avatars directory...');
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '1 sub-directories found.');
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under avatars/{$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Deleted file {$avatar_filename}");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, '1 files deleted.');
        $this->assertFalse(file_exists($avatar_filepath));
    }

    public function testCleanKeepsAvatarsUsedByUsers(): void
    {
        $avatar_filename = $this->createMediaFile('avatars');
        $avatar_filepath = images\Storage::filepath('avatars', $avatar_filename);
        $subdir_name = substr($avatar_filename, 0, 3);
        UserFactory::create([
            'avatar_filename' => $avatar_filename,
        ]);

        $response = $this->appRun('CLI', '/media/clean');

        $this->assertInstanceOf(\Generator::class, $response);
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'Scanning the avatars directory...');
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Removing files under avatars/{$subdir_name}/... (0%)");
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, "Nothing to delete under avatars/{$subdir_name}/.");
        $response->next();
        $response->next();
        $response->next();
        $this->assertResponseCode($response, 200);
        $this->assertResponseEquals($response, 'No files deleted.');
        $this->assertTrue(file_exists($avatar_filepath));
    }

    /**
     * Copy an image in the media directory of the given type, and return its
     * filename.
     *
     * @param MediaType $type
     */
    private function createMediaFile(string $type): string
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/og-card.png';
        $filename = hash_file('sha256', $image_filepath) . '.webp';
        $filepath = images\Storage::filepath($type, $filename);
        @mkdir(dirname($filepath), 0755, true);
        copy($image_filepath, $filepath);
        return $filename;
    }
}
