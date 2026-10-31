<?php

namespace App\controllers\sources\follows;

use App\forms;
use App\models;
use App\utils;
use tests\factories\CollectionFactory;
use tests\factories\FeedFactory;
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
        $feed = FeedFactory::create([
            'name' => $collection_name,
            'is_public' => true,
        ]);
        $user->follow($feed);

        $response = $this->appRun('GET', "/collections/{$feed->id}/follow/image");

        $this->assertResponseCode($response, 200);
        $this->assertResponseTemplateName($response, 'sources/follows/images/edit.html.twig');
        $this->assertResponseContains($response, $collection_name);
    }

    public function testEditRedirectsIfNotConnected(): void
    {
        $feed = FeedFactory::create([
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/collections/{$feed->id}/follow/image");

        $redirect_to = urlencode("/collections/{$feed->id}/follow/image");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
    }

    public function testEditFailsIfSourceIsNotFollowed(): void
    {
        $user = $this->login();
        $feed = FeedFactory::create([
            'is_public' => true,
        ]);

        $response = $this->appRun('GET', "/collections/{$feed->id}/follow/image");

        $this->assertResponseCode($response, 404);
    }

    public function testEditFailsIfSourceIsPrivate(): void
    {
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
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
        $feed = FeedFactory::create([
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($feed);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollowImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 302, "/collections/{$feed->id}/follow/image");
        $user = $user->reload();
        $feed = $feed->reload();
        $image_filename = $feed->imageFilenameByUser($user);
        $this->assertNotNull($image_filename);
        $this->assertNull($feed->image_filename);
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
        $feed = FeedFactory::create([
            'is_public' => true,
        ]);
        $user->follow($feed);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollowImage::class),
            'image' => $file,
        ]);

        $redirect_to = urlencode("/collections/{$feed->id}/follow/image");
        $this->assertResponseCode($response, 302, "/login?redirect_to={$redirect_to}");
        $user = $user->reload();
        $this->assertNull($feed->imageFilenameByUser($user));
    }

    public function testUpdateFailsIfSourceIsNotFollowed(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $feed = FeedFactory::create([
            'is_public' => true,
            'image_filename' => null,
        ]);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollowImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 404);
        $feed = $feed->reload();
        $this->assertNull($feed->image_filename);
    }

    public function testUpdateFailsIfSourceIsPrivate(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-card.png';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $other_user = UserFactory::create();
        $collection = CollectionFactory::create([
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
            'csrf_token' => $this->csrfToken(forms\sources\EditFollowImage::class),
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
        $feed = FeedFactory::create([
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($feed);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/image", [
            'csrf_token' => 'not the token',
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'sources/follows/images/edit.html.twig');
        $this->assertResponseContains($response, 'A security verification failed');
        $user = $user->reload();
        $this->assertNull($feed->imageFilenameByUser($user));
    }

    public function testUpdateFailsIfFileIsMissing(): void
    {
        $user = $this->login();
        $feed = FeedFactory::create([
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($feed);

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollowImage::class),
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'sources/follows/images/edit.html.twig');
        $this->assertResponseContains($response, 'The file is required');
        $user = $user->reload();
        $this->assertNull($feed->imageFilenameByUser($user));
    }

    public function testUpdateFailsIfWrongFileType(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/default-avatar.svg';
        $tmp_filepath = $this->tmpCopyFile($image_filepath);
        $user = $this->login();
        $feed = FeedFactory::create([
            'is_public' => true,
            'image_filename' => null,
        ]);
        $user->follow($feed);
        $file = [
            'tmp_name' => $tmp_filepath,
            'name' => 'image.png',
            'error' => UPLOAD_ERR_OK,
        ];

        $response = $this->appRun('POST', "/collections/{$feed->id}/follow/image", [
            'csrf_token' => $this->csrfToken(forms\sources\EditFollowImage::class),
            'image' => $file,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseTemplateName($response, 'sources/follows/images/edit.html.twig');
        $this->assertResponseContains($response, 'The file type must be one of the following: PNG, JPG, JPEG, WEBP.');
        $user = $user->reload();
        $this->assertNull($feed->imageFilenameByUser($user));
    }
}
