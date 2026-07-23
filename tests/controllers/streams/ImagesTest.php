<?php

namespace App\controllers\streams;

use App\forms;
use App\models;
use App\utils;
use tests\factories\StreamFactory;
use tests\factories\UserFactory;

class ImagesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        /** @var string */
        $stream_name = $this->fake('words', 3, true);
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'name' => $stream_name,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/image");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'streams/images/edit.html.twig');
        $this->assertResponseContains($response, $stream_name);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/image");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fstreams%2F{$stream->id}%2Fimage");
    }

    public function testEditFailsIfStreamDoesNotExist(): void
    {
        $this->login();

        $response = $this->appRun('GET', '/streams/unknown/image');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfUserDoesNotOwnTheStream(): void
    {
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/streams/{$stream->id}/image");

        $this->assertResponseCode($response, 403);
    }

    public function testUpdateSetsImageFilenameAndRedirects(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/streams/{$stream->id}/image", [
            'csrf_token' => $this->csrfToken(forms\streams\EditStreamImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/streams/{$stream->id}/image");
        $stream = $stream->reload();
        $this->assertNotNull($stream->image_filename);
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($stream->image_filename);
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$stream->image_filename}";
        $this->assertTrue(file_exists($cover_filepath));
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/streams/{$stream->id}/image", [
            'csrf_token' => $this->csrfToken(forms\streams\EditStreamImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fstreams%2F{$stream->id}%2Fimage");
        $stream = $stream->reload();
        $this->assertNull($stream->image_filename);
    }

    public function testUpdateFailsIfUserDoesNotOwnTheStream(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $this->login();
        $other_user = UserFactory::create();
        $stream = StreamFactory::create([
            'user_id' => $other_user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/streams/{$stream->id}/image", [
            'csrf_token' => $this->csrfToken(forms\streams\EditStreamImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 403);
        $stream = $stream->reload();
        $this->assertNull($stream->image_filename);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/streams/{$stream->id}/image", [
            'csrf_token' => 'not the token',
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'A security verification failed');
        $stream = $stream->reload();
        $this->assertNull($stream->image_filename);
    }

    public function testUpdateFailsIfFileIsMissing(): void
    {
        $user = $this->login();
        $stream = StreamFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);

        $response = $this->appRun('POST', "/streams/{$stream->id}/image", [
            'csrf_token' => $this->csrfToken(forms\streams\EditStreamImage::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, 'The file is required.');
        $stream = $stream->reload();
        $this->assertNull($stream->image_filename);
    }
}
