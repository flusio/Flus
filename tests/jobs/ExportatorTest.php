<?php

namespace flusio\jobs;

use flusio\models;
use flusio\utils;

class ExportatorTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;

    /**
     * @beforeClass
     */
    public static function setRouterToUrl()
    {
        $router = \flusio\Router::load();
        \Minz\Url::setRouter($router);
    }

    /**
     * @beforeClass
     */
    public static function setJobAdapterToDatabase()
    {
        \Minz\Configuration::$application['job_adapter'] = 'database';
    }

    /**
     * @afterClass
     */
    public static function setJobAdapterToTest()
    {
        \Minz\Configuration::$application['job_adapter'] = 'test';
    }

    public function testQueue()
    {
        $job = new Exportator();

        $this->assertSame('default', $job->queue);
    }

    public function testPerformCreatesAnArchiveAndAcknowledgesTheExportation()
    {
        $job = new Exportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'ongoing',
        ]);

        $job->perform($exportation_id);

        $exportation = models\Exportation::find($exportation_id);
        $this->assertSame('finished', $exportation->status);
        $this->assertNotEmpty($exportation->filepath);
        $this->assertTrue(file_exists($exportation->filepath));
        $exportations_path = \Minz\Configuration::$data_path . '/exportations';
        $this->assertTrue(utils\Belt::startsWith($exportation->filepath, $exportations_path));
    }

    public function testPerformDoesNothingIfStatusIsFinished()
    {
        $job = new Exportator();
        $user_id = $this->create('user');
        $user = models\User::find($user_id);
        $exportation_id = $this->create('exportation', [
            'user_id' => $user->id,
            'status' => 'finished',
            'filepath' => '',
        ]);

        $job->perform($exportation_id);

        $exportation = models\Exportation::find($exportation_id);
        $this->assertSame('finished', $exportation->status);
        $this->assertSame('', $exportation->filepath);
    }
}
