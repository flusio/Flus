<?php

namespace App\migrations;

class Migration202010190001MigrateIds
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $tables_to_migrate = ['links', 'collections'];
        foreach ($tables_to_migrate as $table) {
            $statement = $database->query(<<<SQL
                SELECT id, created_at FROM {$table}
            SQL);

            $database->beginTransaction();

            foreach ($statement->fetchAll() as $row) {
                // We create a new id for this entry. We don't use
                // \Minz\Random::timebased() function because we want
                // the time part as close as possible to the reality represented
                // by the created_at value.
                $created_at = date_create_from_format('Y-m-d H:i:sP', $row['created_at']);

                assert($created_at !== false);

                // Since created_at don't contain the milliseconds part, we
                // fake it with a random value
                $milliseconds = $created_at->getTimestamp() * 1000;
                $milliseconds += random_int(0, 999);

                // Same code as timebased() function
                $time_part = $milliseconds << 20;
                $random_part = random_int(0, 1048575); // max number on 20 bits
                $new_id = strval($time_part | $random_part);

                $statement = $database->prepare(<<<SQL
                    UPDATE {$table} SET id = ? WHERE id = ?;
                SQL);
                $statement->execute([$new_id, $row['id']]);
            }

            $database->commit();
        }

        return true;
    }

    public function rollback(): bool
    {
        // do nothing, just keep the new ids if set, we can't find the old ones
        // anyway
        return true;
    }
}
