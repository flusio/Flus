<?php

namespace flusio\cli;

use flusio\models;

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
        $topic_dao = new models\dao\Topic();
        $label = $this->fake('word');

        $this->assertSame(0, $topic_dao->count());

        $response = $this->appRun('cli', '/topics/create', [
            'label' => $label,
        ]);

        $this->assertResponse($response, 200, 'has been created');
        $this->assertSame(1, $topic_dao->count());
    }

    public function testCreateFailsIfLabelIsTooLong()
    {
        $topic_dao = new models\dao\Topic();
        $label_max_size = models\Topic::LABEL_MAX_SIZE;
        $size = $label_max_size + $this->fake('randomDigitNotNull');
        $label = $this->fake('regexify', "\w{{$size}}");

        $response = $this->appRun('cli', '/topics/create', [
            'label' => $label,
        ]);

        $this->assertResponse($response, 400, "The label must be less than {$label_max_size} characters.");
        $this->assertSame(0, $topic_dao->count());
    }

    public function testCreateFailsIfLabelIsEmpty()
    {
        $topic_dao = new models\dao\Topic();

        $response = $this->appRun('cli', '/topics/create', [
            'label' => '',
        ]);

        $this->assertResponse($response, 400, "The label is required.");
        $this->assertSame(0, $topic_dao->count());
    }

    public function testDeleteDeletesTopic()
    {
        $topic_dao = new models\dao\Topic();
        $topic_id = $this->create('topic');

        $this->assertSame(1, $topic_dao->count());

        $response = $this->appRun('cli', '/topics/delete', [
            'id' => $topic_id,
        ]);

        $this->assertResponse($response, 200, 'has been deleted');
        $this->assertSame(0, $topic_dao->count());
    }

    public function testDeleteFailsIfIdIsInvalid()
    {
        $topic_dao = new models\dao\Topic();
        $topic_id = $this->create('topic');

        $response = $this->appRun('cli', '/topics/delete', [
            'id' => 'not an id',
        ]);

        $this->assertResponse($response, 404, 'Topic id `not an id` does not exist.');
        $this->assertSame(1, $topic_dao->count());
    }
}
