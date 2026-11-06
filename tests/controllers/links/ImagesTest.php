<?php

namespace App\controllers\links;

use App\forms;
use App\images;
use App\models;
use tests\factories\LinkFactory;
use tests\factories\UserFactory;

class ImagesTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\CsrfHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;
    use \tests\LoginHelper;

    public function testEditRendersCorrectly(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/image");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/links/{$link->id}/image");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fimage");
    }

    public function testEditFailsIfLinkDoesNotExist(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', '/links/not-an-id/image');

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateSetsImageFilenameAndRedirects(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/image");
        $link = $link->reload();
        $expected_filename = hash_file('sha256', $image_filepath) . '.webp';
        $this->assertSame($expected_filename, $link->image_filename);
        $this->assertTrue(file_exists(images\Storage::filepath('covers', $expected_filename)));
    }

    public function testUpdateKeepsTheImageOfCopiedLinks(): void
    {
        $previous_image_filepath = \App\Configuration::$app_path . '/public/static/og-card.png';
        $previous_image_filename = hash_file('sha256', $previous_image_filepath) . '.webp';
        $previous_cover_filepath = images\Storage::filepath('covers', $previous_image_filename);
        @mkdir(dirname($previous_cover_filepath), 0755, true);
        copy($previous_image_filepath, $previous_cover_filepath);
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => $previous_image_filename,
        ]);
        $copied_link = models\Link::copy($link, UserFactory::create());
        $copied_link->save();

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => [
                'tmp_name' => $tmp_filepath,
                'name' => 'image.png',
                'error' => UPLOAD_ERR_OK,
            ],
        ]);

        $this->assertResponseCode($response, 302, "/links/{$link->id}/image");
        $link = $link->reload();
        $copied_link = $copied_link->reload();
        $image_filename = hash_file('sha256', $image_filepath) . '.webp';
        $this->assertSame($image_filename, $link->image_filename);
        $this->assertSame($previous_image_filename, $copied_link->image_filename);
        $this->assertFileEquals($previous_image_filepath, $previous_cover_filepath);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = UserFactory::create();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Flinks%2F{$link->id}%2Fimage");
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    public function testUpdateFailsIfLinkDoesNotExist(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/links/not-an-id/image', [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 404);
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => 'not the token',
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
        $this->assertResponseContains($response, 'A security verification failed');
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    public function testUpdateFailsIfFileIsMissing(): void
    {
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
        $this->assertResponseContains($response, 'The file is required');
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    public function testUpdateFailsIfWrongFileType(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
        $this->assertResponseContains($response, 'The file type must be one of the following: PNG, JPG, JPEG, WEBP.');
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    public function testUpdateFailsIfImageIsCorrupted(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/tests/fixtures/images/corrupted.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
        $this->assertResponseContains($response, 'This image cannot be read or its dimensions are too large.');
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    public function testUpdateFailsIfIsUploadedFileReturnsFalse(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
        $this->assertResponseContains($response, 'This file cannot be uploaded (error -1).');
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tooLargeErrorsProvider')]
    public function testUpdateFailsIfTooLarge(int $error): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => $error,
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
        $this->assertResponseContains($response, 'This file is too large');
        $link = $link->reload();
        $this->assertNull($link->image_filename);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('otherFileErrorsProvider')]
    public function testUpdateFailsIfFileFailedToUpload(int $error): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $link = LinkFactory::create([
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => $error,
        ];

        $response = $this->appRun('POST', "/links/{$link->id}/image", [
            'csrf_token' => $this->csrfToken(forms\links\EditLinkImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'links/images/edit.html.twig');
        $this->assertResponseContains($response, "This file cannot be uploaded (error {$error}).");
        $link = $link->reload();
        $this->assertNull($link->image_filename);
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
