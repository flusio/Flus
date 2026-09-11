<?php

namespace App\controllers\collections\followers;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
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
            'type' => 'feed',
            'name' => $collection_name,
            'is_public' => true,
        ]);
        $user->follow($collection);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/image");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'collections/followers/images/edit.html.twig');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/image");

        $redirect_to = urlencode("/collections/{$collection->id}/follow/image");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testEditFailsIfCollectionIsNotFollowed(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/image");

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfCollectionIsPrivate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $user->follow($collection);

        $response = $this->appRun('GET', "/collections/{$collection->id}/follow/image");

        $this->assertResponseCode($response, 403);
    }

    public function testUpdateSetsTheImageOfTheFollowAndRedirectsToFrom(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($collection);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollowImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$collection->id}/follow/image");
        $user = $user->reload();
        $collection = $collection->reload();
        $image_filename = $collection->imageFilenameByUser($user);
        $this->assertNotNull($image_filename);
        $this->assertNull($collection->image_filename);
        $media_path = \App\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($cover_filepath));
    }

    public function testUpdateRedirectsIfNotConnected(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
        ]);
        $user->follow($collection);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollowImage::class),
            'image' => $file,
        ]);

        $redirect_to = urlencode("/collections/{$collection->id}/follow/image");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
        $user = $user->reload();
        $this->assertNull($collection->imageFilenameByUser($user));
    }

    public function testUpdateFailsIfCollectionIsNotFollowed(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollowImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 404);
        $collection = $collection->reload();
        $this->assertNull($collection->image_filename);
    }

    public function testUpdateFailsIfCollectionIsPrivate(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
            'type' => 'collection',
            'user_id' => $other_user->id,
            'is_public' => false,
        ]);
        $user->follow($collection);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollowImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 403);
        $user = $user->reload();
        $this->assertNull($collection->imageFilenameByUser($user));
    }

    public function testUpdateFailsIfCsrfIsInvalid(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($collection);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/image", [
            'csrf_token' => 'not the token',
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/followers/images/edit.html.twig');
        $this->assertResponseContains($response, 'A security verification failed');
        $user = $user->reload();
        $this->assertNull($collection->imageFilenameByUser($user));
    }

    public function testUpdateFailsIfFileIsMissing(): void
    {
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($collection);

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollowImage::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/followers/images/edit.html.twig');
        $this->assertResponseContains($response, 'The file is required');
        $user = $user->reload();
        $this->assertNull($collection->imageFilenameByUser($user));
    }

    public function testUpdateFailsIfWrongFileType(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $collection = CollectionFactory::create([
            'type' => 'feed',
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($collection);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$collection->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\collections\EditFollowImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'collections/followers/images/edit.html.twig');
        $this->assertResponseContains($response, 'The file type must be one of the following: PNG, JPG, JPEG, WEBP.');
        $user = $user->reload();
        $this->assertNull($collection->imageFilenameByUser($user));
    }
}
