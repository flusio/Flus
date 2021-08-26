<?php

namespace flusio\controllers\collections;

use flusio\models;

class ImagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\FilesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;

    public function testEditRendersCorrectly()
    {
        $user = $this->login();
        $collection_name = $this->fake('text', 50);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'name' => $collection_name,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/image", [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponsePointer($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRedirectsIfNotConnected()
    {
        $user_id = $this->create('user');
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user_id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', "/collections/{$collection_id}/image", [
            'from' => $from,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
    }

    public function testEditFailsIfCollectionDoesNotExist()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('get', '/collections/not-an-id/image', [
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 404);
    }

    public function testUpdateRedirectsToFrom()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => $user->csrf,
            'from' => $from,
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, $from);
    }

    public function testUpdateSetsImageFilename()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => $user->csrf,
            'from' => $from,
            'image' => $file,
        ]);

        $collection = models\Collection::find($collection_id);
        $this->assertNotNull($collection->image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $card_filepath = "{$media_path}/cards/{$collection->image_filename}";
        $cover_filepath = "{$media_path}/covers/{$collection->image_filename}";
        $large_filepath = "{$media_path}/large/{$collection->image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }

    public function testUpdateRedirectsIfNotConnected()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user_id = $this->create('user', [
            'csrf' => 'a token',
        ]);
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user_id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => 'a token',
            'from' => $from,
            'image' => $file,
        ]);

        $from_encoded = urlencode($from);
        $this->assertResponseCode($response, 302, "/login?redirect_to={$from_encoded}");
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfCollectionDoesNotExist()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', '/collections/not-an-id/image', [
            'csrf' => $user->csrf,
            'from' => $from,
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 404);
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfCsrfIsInvalid()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => 'not the token',
            'from' => $from,
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'A security verification failed');
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfFileIsMissing()
    {
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => $user->csrf,
            'from' => $from,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'The file is required');
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfWrongFileType()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => $user->csrf,
            'from' => $from,
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'The photo must be <abbr>PNG</abbr> or <abbr>JPG</abbr>');
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfIsUploadedFileReturnsFalse()
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => UPLOAD_ERR_OK,
            'is_uploaded_file' => false, // this is possible only during tests!
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => $user->csrf,
            'from' => $from,
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'This file cannot be uploaded (error -1).');
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testUpdateFailsIfTooLarge($error)
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => $user->csrf,
            'from' => $from,
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, 'This file is too large');
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    /**
     * @dataProvider otherFileErrorsProvider
     */
    public function testUpdateFailsIfFileFailedToUpload($error)
    {
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'error' => $error,
        ];
        $from = \Minz\Url::for('collection', ['id' => $collection_id]);

        $response = $this->appRun('post', "/collections/{$collection_id}/image", [
            'csrf' => $user->csrf,
            'from' => $from,
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponsePointer($response, 'collections/images/edit.phtml');
        $this->assertResponseContains($response, "This file cannot be uploaded (error {$error}).");
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
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
