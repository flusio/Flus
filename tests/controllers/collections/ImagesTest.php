<?php

namespace flusio\controllers\collections;

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
function is_uploaded_file($filename)
{
    return $filename !== 'not_uploaded_file';
}

class ImagesTest extends \PHPUnit\Framework\TestCase
{
    use \tests\LoginHelper;
    use \tests\FakerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\FactoriesHelper;
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
        // we copy an existing file as a tmp file to simulate an image upload
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($image_filepath, $tmp_filepath);

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
        // we copy an existing file as a tmp file to simulate an image upload
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($image_filepath, $tmp_filepath);

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
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;

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
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;

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
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;

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
        // we copy an existing file as a tmp file to simulate an image upload
        $image_filepath = \Minz\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;
        copy($image_filepath, $tmp_filepath);

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
        $user = $this->login();
        $collection_id = $this->create('collection', [
            'type' => 'collection',
            'user_id' => $user->id,
            'image_filename' => null,
        ]);
        $file = [
            // see is_uploaded_file override at the top of the file: it will
            // return false if the tmp_name is 'not_uploaded_file'.
            'tmp_name' => 'not_uploaded_file',
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
        $this->assertResponseContains($response, 'This file cannot be uploaded');
        $collection = models\Collection::find($collection_id);
        $this->assertNull($collection->image_filename);
    }

    /**
     * @dataProvider tooLargeErrorsProvider
     */
    public function testUpdateFailsIfTooLarge($error)
    {
        // we copy an existing file as a tmp file to simulate an image upload
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;

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
        // we copy an existing file as a tmp file to simulate an image upload
        $tmp_path = \Minz\Configuration::$application['tmp_path'];
        $tmp_filename = $this->fakeUnique('md5') . '.png';
        $tmp_filepath = $tmp_path . '/' . $tmp_filename;

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
