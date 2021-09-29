<?php

namespace flusio\cli;

use flusio\models;
use flusio\utils;

class TopicsTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\ResponseAsserts;

    /**
     * @beforeClass
     */
    public static function loadApplication()
    {
        self::$application = new \flusio\cli\Application();
    }

    /**
     * @before
     */
    public function emptyCachePath()
    {
        $files = glob(\Minz\Configuration::$application['cache_path'] . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
    }

    public function testIndexRendersCorrectly()
    {
        $label1 = $this->fake('word');
        $label2 = $this->fake('word');
        $this->create('topic', [
            'label' => $label1,
        ]);
        $this->create('topic', [
            'label' => $label2,
        ]);

        $response = $this->appRun('cli', '/topics');

        $this->assertResponse($response, 200);
        $output = $response->render();
        $this->assertStringContainsString($label1, $output);
        $this->assertStringContainsString($label2, $output);
    }

    public function testCreateCreatesATopicAndRendersCorrectly()
    {
        $label = $this->fake('word');

        $this->assertSame(0, models\Topic::count());

        $response = $this->appRun('cli', '/topics/create', [
            'label' => $label,
        ]);

        $this->assertResponse($response, 200, 'has been created');
        $this->assertSame(1, models\Topic::count());
    }

    public function testCreateDownloadsImageIfPassed()
    {
        $label = $this->fake('word');
        $image_url = 'https://flus.fr/carnet/card.png';

        $response = $this->appRun('cli', '/topics/create', [
            'label' => $label,
            'image_url' => $image_url,
        ]);

        $topic = models\Topic::take();
        $image_filename = $topic->image_filename;
        $this->assertNotNull($image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $card_filepath = "{$media_path}/cards/{$subpath}/{$image_filename}";
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $large_filepath = "{$media_path}/large/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }

    public function testCreateFailsIfLabelIsTooLong()
    {
        $label_max_size = models\Topic::LABEL_MAX_SIZE;
        $size = $label_max_size + $this->fake('randomDigitNotNull');
        $label = $this->fake('regexify', "\w{{$size}}");

        $response = $this->appRun('cli', '/topics/create', [
            'label' => $label,
        ]);

        $this->assertResponse($response, 400, "The label must be less than {$label_max_size} characters.");
        $this->assertSame(0, models\Topic::count());
    }

    public function testCreateFailsIfLabelIsEmpty()
    {
        $response = $this->appRun('cli', '/topics/create', [
            'label' => '',
        ]);

        $this->assertResponse($response, 400, "The label is required.");
        $this->assertSame(0, models\Topic::count());
    }

    public function testUpdateChangesLabelIfPassed()
    {
        $old_label = $this->fakeUnique('word');
        $new_label = $this->fakeUnique('word');
        $topic_id = $this->create('topic', [
            'label' => $old_label,
        ]);

        $response = $this->appRun('cli', '/topics/update', [
            'id' => $topic_id,
            'label' => $new_label,
        ]);

        $this->assertResponse($response, 200, 'has been updated');
        $topic = models\Topic::find($topic_id);
        $this->assertSame($new_label, $topic->label);
    }

    public function testUpdateChangesImageFilenameIfPassed()
    {
        $old_image_filename = $this->fakeUnique('sha256') . '.jpg';
        $image_url = 'https://flus.fr/carnet/card.png';
        $topic_id = $this->create('topic', [
            'image_filename' => $old_image_filename,
        ]);

        $response = $this->appRun('cli', '/topics/update', [
            'id' => $topic_id,
            'image_url' => $image_url,
        ]);

        $this->assertResponse($response, 200, 'has been updated');
        $topic = models\Topic::find($topic_id);
        $image_filename = $topic->image_filename;
        $this->assertNotSame($old_image_filename, $image_filename);
        $media_path = \Minz\Configuration::$application['media_path'];
        $subpath = utils\Belt::filenameToSubpath($image_filename);
        $card_filepath = "{$media_path}/cards/{$subpath}/{$image_filename}";
        $cover_filepath = "{$media_path}/covers/{$subpath}/{$image_filename}";
        $large_filepath = "{$media_path}/large/{$subpath}/{$image_filename}";
        $this->assertTrue(file_exists($card_filepath));
        $this->assertTrue(file_exists($cover_filepath));
        $this->assertTrue(file_exists($large_filepath));
    }

    public function testUpdateDoesNothingIfLabelIsEmpty()
    {
        $old_label = $this->fakeUnique('word');
        $new_label = '';
        $topic_id = $this->create('topic', [
            'label' => $old_label,
        ]);

        $response = $this->appRun('cli', '/topics/update', [
            'id' => $topic_id,
            'label' => $new_label,
        ]);

        $this->assertResponse($response, 200, 'has been updated');
        $topic = models\Topic::find($topic_id);
        $this->assertSame($old_label, $topic->label);
    }

    public function testUpdateFailsIfIdIsInvalid()
    {
        $old_label = $this->fakeUnique('word');
        $new_label = $this->fakeUnique('word');
        $topic_id = $this->create('topic', [
            'label' => $old_label,
        ]);

        $response = $this->appRun('cli', '/topics/update', [
            'id' => 'not an id',
            'label' => $new_label,
        ]);

        $this->assertResponse($response, 404, 'Topic id `not an id` does not exist.');
        $topic = models\Topic::find($topic_id);
        $this->assertSame($old_label, $topic->label);
    }

    public function testUpdateFailsIfLabelIsTooLong()
    {
        $old_label = $this->fakeUnique('word');
        $label_max_size = models\Topic::LABEL_MAX_SIZE;
        $size = $label_max_size + $this->fake('randomDigitNotNull');
        $new_label = $this->fake('regexify', "\w{{$size}}");
        $topic_id = $this->create('topic', [
            'label' => $old_label,
        ]);

        $response = $this->appRun('cli', '/topics/update', [
            'id' => $topic_id,
            'label' => $new_label,
        ]);

        $this->assertResponse($response, 400, "The label must be less than {$label_max_size} characters.");
        $topic = models\Topic::find($topic_id);
        $this->assertSame($old_label, $topic->label);
    }

    public function testDeleteDeletesTopic()
    {
        $topic_id = $this->create('topic');

        $this->assertSame(1, models\Topic::count());

        $response = $this->appRun('cli', '/topics/delete', [
            'id' => $topic_id,
        ]);

        $this->assertResponse($response, 200, 'has been deleted');
        $this->assertSame(0, models\Topic::count());
    }

    public function testDeleteFailsIfIdIsInvalid()
    {
        $topic_id = $this->create('topic');

        $response = $this->appRun('cli', '/topics/delete', [
            'id' => 'not an id',
        ]);

        $this->assertResponse($response, 404, 'Topic id `not an id` does not exist.');
        $this->assertSame(1, models\Topic::count());
    }
}
