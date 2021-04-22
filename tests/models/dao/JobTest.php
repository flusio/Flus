<?php

namespace flusio\models\dao;

class JobTest extends \PHPUnit\Framework\TestCase
{
    use \tests\FakerHelper;
    use \Minz\Tests\InitializerHelper;
    use \Minz\Tests\FactoriesHelper;
    use \Minz\Tests\TimeHelper;

    public function testFindNextJobReturnsJobWithPastPerformAt()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
        ]);

        $db_job = $job_dao->findNextJob('all');

        $this->assertSame($job_id, $db_job['id']);
    }

    public function testFindNextJobReturnsJobTakingTheOldestOne()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $created_at_1 = \Minz\Time::ago($minutes_ago, 'minutes');
        $created_at_2 = \Minz\Time::ago($minutes_ago + 5, 'minutes');
        $job_dao = new Job();
        $job_id_1 = $this->create('job', [
            'created_at' => $created_at_1->format(\Minz\Model::DATETIME_FORMAT),
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
        ]);
        $job_id_2 = $this->create('job', [
            'created_at' => $created_at_2->format(\Minz\Model::DATETIME_FORMAT),
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
        ]);

        $db_job = $job_dao->findNextJob('all');

        $this->assertSame($job_id_2, $db_job['id']);
    }

    public function testFindNextJobGivesPriorityToJobsWithNoFrequency()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $created_at_1 = \Minz\Time::ago($minutes_ago, 'minutes');
        $created_at_2 = \Minz\Time::ago($minutes_ago + 5, 'minutes');
        $job_dao = new Job();
        $job_id_1 = $this->create('job', [
            'created_at' => $created_at_1->format(\Minz\Model::DATETIME_FORMAT),
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
        ]);
        $job_id_2 = $this->create('job', [
            'created_at' => $created_at_2->format(\Minz\Model::DATETIME_FORMAT),
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '+5 minutes',
        ]);

        $db_job = $job_dao->findNextJob('all');

        $this->assertSame($job_id_1, $db_job['id']);
    }

    public function testFindNextJobReturnsJobInGivenQueue()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $queue = $this->fake('word');
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
            'queue' => $queue,
        ]);

        $db_job = $job_dao->findNextJob($queue);

        $this->assertSame($job_id, $db_job['id']);
    }

    public function testFindNextJobReturnsJobWithMoreThan25AttempsAndFrequency()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 26, 9000);
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '+5 minutes',
        ]);

        $db_job = $job_dao->findNextJob('all');

        $this->assertSame($job_id, $db_job['id']);
    }

    public function testFindNextJobDoesNotReturnJobWithFuturePerformAt()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_from_now = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::fromNow($minutes_from_now, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
        ]);

        $db_job = $job_dao->findNextJob('all');

        $this->assertNull($db_job);
    }

    public function testFindNextJobDoesNotReturnJobWithMoreThan25AttempsAndNoFrequency()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 26, 9000);
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
        ]);

        $db_job = $job_dao->findNextJob('all');

        $this->assertNull($db_job);
    }

    public function testFindNextJobDoesNotReturnJobIfQueueDoesNotMatch()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $queue_job = $this->fake('word');
        $queue_requested = $this->fake('word');
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => null,
            'number_attempts' => $number_attempts,
            'frequency' => '',
            'queue' => $queue_job,
        ]);

        $db_job = $job_dao->findNextJob($queue_requested);

        $this->assertNull($db_job);
    }

    public function testFindNextJobDoesNotReturnLockedJobs()
    {
        $now = $this->fake('dateTime');
        $this->freeze($now);
        $minutes_ago = $this->fake('randomNumber');
        $number_attempts = $this->fake('numberBetween', 0, 25);
        $locked_at = $this->fake('dateTime');
        $job_dao = new Job();
        $job_id = $this->create('job', [
            'perform_at' => \Minz\Time::ago($minutes_ago, 'minutes')->format(\Minz\Model::DATETIME_FORMAT),
            'locked_at' => $locked_at->format(\Minz\Model::DATETIME_FORMAT),
            'number_attempts' => $number_attempts,
            'frequency' => '',
        ]);

        $db_job = $job_dao->findNextJob('all');

        $this->assertNull($db_job);
    }

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
