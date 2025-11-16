<?php

namespace App\cli;

use App\models;
use App\utils;
use tests\factories\TopicFactory;

class TopicsTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\ApplicationHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\ResponseAsserts;
    use \tests\FakerHelper;
    use \tests\FilesystemHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function loadApplication(): void
    {
        self::$application = new \App\cli\Application();
    }

    public function testIndexRendersCorrectly(): void
    {
        /** @var string */
        $label1 = $this->fake('word');
        /** @var string */
        $label2 = $this->fake('word');
        TopicFactory::create([
            'label' => $label1,
        ]);
        TopicFactory::create([
            'label' => $label2,
        ]);

        $response = $this->appRun('CLI', '/topics');

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, $label1);
        $this->assertResponseContains($response, $label2);
    }

    public function testCreateCreatesATopicAndRendersCorrectly(): void
    {
        /** @var string */
        $label = $this->fake('word');

        $this->assertSame(0, models\Topic::count());

        $response = $this->appRun('CLI', '/topics/create', [
            'label' => $label,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'has been created');
        $this->assertSame(1, models\Topic::count());
    }

    public function testCreateFailsIfLabelIsTooLong(): void
    {
        $label_max_size = models\Topic::LABEL_MAX_SIZE;
        /** @var int */
        $size = $this->fake('randomDigitNotNull');
        $size = $size + $label_max_size;
        /** @var string */
        $label = $this->fake('regexify', "\w{{$size}}");

        $response = $this->appRun('CLI', '/topics/create', [
            'label' => $label,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "The label must be less than {$label_max_size} characters.");
        $this->assertSame(0, models\Topic::count());
    }

    public function testCreateFailsIfLabelIsEmpty(): void
    {
        $response = $this->appRun('CLI', '/topics/create', [
            'label' => '',
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "The label is required.");
        $this->assertSame(0, models\Topic::count());
    }

    public function testUpdateChangesLabelIfPassed(): void
    {
        /** @var string */
        $old_label = $this->fakeUnique('word');
        /** @var string */
        $new_label = $this->fakeUnique('word');
        $topic = TopicFactory::create([
            'label' => $old_label,
        ]);

        $response = $this->appRun('CLI', '/topics/update', [
            'id' => $topic->id,
            'label' => $new_label,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'has been updated');
        $topic = $topic->reload();
        $this->assertSame($new_label, $topic->label);
    }

    public function testUpdateDoesNothingIfLabelIsEmpty(): void
    {
        /** @var string */
        $old_label = $this->fakeUnique('word');
        $new_label = '';
        $topic = TopicFactory::create([
            'label' => $old_label,
        ]);

        $response = $this->appRun('CLI', '/topics/update', [
            'id' => $topic->id,
            'label' => $new_label,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'has been updated');
        $topic = $topic->reload();
        $this->assertSame($old_label, $topic->label);
    }

    public function testUpdateFailsIfIdIsInvalid(): void
    {
        /** @var string */
        $old_label = $this->fakeUnique('word');
        /** @var string */
        $new_label = $this->fakeUnique('word');
        $topic = TopicFactory::create([
            'label' => $old_label,
        ]);

        $response = $this->appRun('CLI', '/topics/update', [
            'id' => 'not an id',
            'label' => $new_label,
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'Topic id `not an id` does not exist.');
        $topic = $topic->reload();
        $this->assertSame($old_label, $topic->label);
    }

    public function testUpdateFailsIfLabelIsTooLong(): void
    {
        /** @var string */
        $old_label = $this->fakeUnique('word');
        $label_max_size = models\Topic::LABEL_MAX_SIZE;
        /** @var int */
        $size = $this->fake('randomDigitNotNull');
        $size = $size + $label_max_size;
        /** @var string */
        $new_label = $this->fake('regexify', "\w{{$size}}");
        $topic = TopicFactory::create([
            'label' => $old_label,
        ]);

        $response = $this->appRun('CLI', '/topics/update', [
            'id' => $topic->id,
            'label' => $new_label,
        ]);

        $this->assertResponseCode($response, 400);
        $this->assertResponseContains($response, "The label must be less than {$label_max_size} characters.");
        $topic = $topic->reload();
        $this->assertSame($old_label, $topic->label);
    }

    public function testDeleteDeletesTopic(): void
    {
        $topic = TopicFactory::create();

        $this->assertSame(1, models\Topic::count());

        $response = $this->appRun('CLI', '/topics/delete', [
            'id' => $topic->id,
        ]);

        $this->assertResponseCode($response, 200);
        $this->assertResponseContains($response, 'has been deleted');
        $this->assertSame(0, models\Topic::count());
    }

    public function testDeleteFailsIfIdIsInvalid(): void
    {
        $topic = TopicFactory::create();

        $response = $this->appRun('CLI', '/topics/delete', [
            'id' => 'not an id',
        ]);

        $this->assertResponseCode($response, 404);
        $this->assertResponseEquals($response, 'Topic id `not an id` does not exist.');
        $this->assertSame(1, models\Topic::count());
    }
}
