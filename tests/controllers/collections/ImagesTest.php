<?php

namespace App\controllers\collections;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\CollectionShareFactory;
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
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/image");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        /** @var string */
        $collection_name = $this->fake('text', 50);
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'name' => $collection_name,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/image");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/image");

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Fimage");
    }

    public function testEditFailsIfCollectionDoesNotExist(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
        ]);

        $response = $this->appRun('GET', '/collections/not-an-id/image');

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsNotShared(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/image");

        $this->assertResponseCode($response, 403);
    }

    public function testEditFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/image");

        $this->assertResponseCode($response, 403);
    }

    public function testUpdateRedirectsToFrom(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/image");
    }

    public function testUpdateSetsImageFilename(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $collection = $collection->reload();
        $this->assertNotNull($collection->image_filename);
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($collection->image_filename);
        $card_filepath = "{$media_path}/cards/{$subpath}/{$collection->image_filename}";
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$collection->image_filename}";
        $large_filepath = "{$media_path}/large/{$subpath}/{$collection->image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }

    public function testUpdateWorksIfCollectionIsSharedWithWriteAccess(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'image_filename' => null,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'write',
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/image");
        $collection = $collection->reload();
        $this->assertNotNull($collection->image_filename);
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/login?redirect_to=%2Fcollections%2F{$collection->id}%2Fimage");
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfCollectionDoesNotExist(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', '/collections/not-an-id/image', [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 404);
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfCollectionIsNotShared(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 403);
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfCollectionIsSharedWithReadAccess(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'image_filename' => null,
        ]);
        CollectionShareFactory::create([
            'collection_id' => $collection->id,
            'user_id' => $user->id,
            'type' => 'read',
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 403);
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => 'not the token',
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfFileIsMissing(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'The file is required');
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfWrongFileType(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'The file type must be one of the following: PNG, JPG, JPEG, WEBP.');
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfIsUploadedFileReturnsFalse(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'This file cannot be uploaded (error -1).');
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tooLargeErrorsProvider')]
    public function testUpdateFailsIfTooLarge(int $error): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => $error,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'This file is too large');
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('otherFileErrorsProvider')]
    public function testUpdateFailsIfFileFailedToUpload(int $error): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => $error,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditCollectionImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, "This file cannot be uploaded (error {$error}).");
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
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
