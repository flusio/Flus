<?php

namespace flusio\jobs;

use flusio\models;

class JobTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \tests\InitializerHelper;
    use \Minz\Tests\TimeHelper;

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

    public function testPerformLater()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $job = new \tests\jobs\MyJob();

        $this->assertSame(0, $job_dao->count());

        $job_id = $job->performLater('some', 'args', 42);

        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $handler = json_decode($db_job['handler'], true);
        $created_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['created_at']);
        $perform_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['perform_at']);
        $this->assertSame($now->getTimestamp(), $created_at->getTimestamp());
        $this->assertSame($now->getTimestamp(), $perform_at->getTimestamp());
        $this->assertSame('tests\\jobs\\MyJob', $handler['job_class']);
        $this->assertSame(['some', 'args', 42], $handler['job_args']);
    }

    public function testPerformLaterWithPerfomAt()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new models\dao\Job();
        $job = new \tests\jobs\MyJob();
        $expected_perform_at = $this->fake('dateTime');

        $job->perform_at = $expected_perform_at;
        $job_id = $job->performLater();

        $this->assertSame(1, $job_dao->count());
        $db_job = $job_dao->find($job_id);
        $perform_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['perform_at']);
        $this->assertSame($expected_perform_at->getTimestamp(), $perform_at->getTimestamp());
    }
}
