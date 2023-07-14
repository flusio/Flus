<?php

namespace flusio\jobs;

use flusio\models;
use tests\factories\ExportationFactory;
use tests\factories\UserFactory;

class ExportatorTest extends \PHPUnit\Framework\TestCase
{
    use \tests\InitializerHelper;

    /**
     * @beforeClass
     */
    public static function initEngine()
    {
        $router = \flusio\Router::load();
        \Minz\Engine::init($router);
    }

    /**
     * @beforeClass
     */
    public static function setJobAdapterToDatabase()
    {
        \Minz\Configuration::$jobs_adapter = 'database';
    }

    /**
     * @afterClass
     */
    public static function setJobAdapterToTest()
    {
        \Minz\Configuration::$jobs_adapter = 'test';
    }

    public function testQueue()
    {
        $job = new Exportator();

        $this->assertSame('default', $job->queue);
    }

    public function testPerformCreatesAnArchiveAndAcknowledgesTheExportation()
    {
        $job = new Exportator();
        $user = UserFactory::create();
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $job->perform($exportation->id);

        $exportation = $exportation->reload();
        $this->assertSame('finished', $exportation->status);
        $this->assertNotEmpty($exportation->filepath);
        $this->assertTrue(file_exists($exportation->filepath));
        $exportations_path = \Minz\Configuration::$data_path . '/exportations';
        $this->assertTrue(str_starts_with($exportation->filepath, $exportations_path));
    }

    public function testPerformDoesNothingIfStatusIsFinished()
    {
        $job = new Exportator();
        $user = UserFactory::create();
        $exportation = ExportationFactory::create([
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => '',
        ]);

        $job->perform($exportation->id);

        $exportation = $exportation->reload();
        $this->assertSame('finished', $exportation->status);
        $this->assertSame('', $exportation->filepath);
    }
}
