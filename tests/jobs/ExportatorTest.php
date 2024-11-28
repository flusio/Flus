<?php

namespace App\jobs;

use tests\factories\ExportationFactory;
use tests\factories\UserFactory;

class ExportatorTest extends \PHPUnit\Framework\TestCase
{
    use \Minz\Tests\InitializerHelper;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initEngine(): void
    {
        $router = \App\Router::load();
        \Minz\Engine::init($router);
    }

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function setJobAdapterToDatabase(): void
    {
        \App\Configuration::$jobs_adapter = 'database';
    }

    #[\PHPUnit\Framework\Attributes\AfterClass]
    public static function setJobAdapterToTest(): void
    {
        \App\Configuration::$jobs_adapter = 'test';
    }

    public function testQueue(): void
    {
        $job = new Exportator();

        $this->assertSame('default', $job->queue);
    }

    public function testPerformCreatesAnArchiveAndAcknowledgesTheExportation(): void
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
        $exportations_path = \App\Configuration::$data_path . '/exportations';
        $this->assertTrue(str_starts_with($exportation->filepath, $exportations_path));
    }

    public function testPerformDoesNothingIfStatusIsFinished(): void
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
