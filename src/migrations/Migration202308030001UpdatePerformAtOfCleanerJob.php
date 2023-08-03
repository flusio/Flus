<?php

namespace flusio\migrations;

class Migration202308030001UpdatePerformAtOfCleanerJob
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $statement = $database->prepare(<<<'SQL'
            UPDATE jobs
            SET perform_at = ?
            WHERE name = 'flusio\jobs\scheduled\Cleaner';
        SQL);

        $perform_at = \Minz\Time::relative('tomorrow 1:00');
        $statement->execute([
            $perform_at->format(\Minz\Database\Column::DATETIME_FORMAT),
        ]);

        return true;
    }

    public function rollback(): bool
    {
        // Do nothing on purpose
        return true;
    }
}
