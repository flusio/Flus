<?php

namespace flusio\models\dao;

class JobTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;

    public function testLockSetsLockedAtAndReturnsTrue()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'locked_at' => null,
        ]);

        $is_locked = $job_dao->lock($job_id);

        $this->assertTrue($is_locked);
        $db_job = $job_dao->find($job_id);
        $locked_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['locked_at']);
        $this->assertSame($now->getTimestamp(), $locked_at->getTimestamp());
    }

    public function testLockIncrementsNumberAttempts()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'locked_at' => null,
            'number_attempts' => 12,
        ]);

        $is_locked = $job_dao->lock($job_id);

        $db_job = $job_dao->find($job_id);
        $this->assertSame(13, $db_job['number_attempts']);
    }

    public function testLockReturnsFalseIfAlreadyLocked()
    {
        $now = $this->fake('dateTime');
        $expected_locked_at = $this->fake('dateTime');
        $this->freeze($now);
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'locked_at' => $expected_locked_at->format(\Minz\Model::DATETIME_FORMAT),
        ]);

        $is_locked = $job_dao->lock($job_id);

        $this->assertFalse($is_locked);
        $db_job = $job_dao->find($job_id);
        $locked_at = date_create_from_format(\Minz\Model::DATETIME_FORMAT, $db_job['locked_at']);
        $this->assertSame($expected_locked_at->getTimestamp(), $locked_at->getTimestamp());
    }
}
