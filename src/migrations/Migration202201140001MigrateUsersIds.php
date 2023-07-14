<?php

namespace flusio\migrations;

class Migration202201140001MigrateUsersIds
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $support_email = \Minz\Configuration::$application['support_email'];

        $statement = $database->query(<<<'SQL'
            SELECT id, created_at, email FROM users
        SQL);

        $database->beginTransaction();

        foreach ($statement->fetchAll() as $row) {
            // Don't migrate the support user: it has too many relations in
            // production and I don't want to see if the database can handle it
            // or not.
            if ($row['email'] === $support_email) {
                continue;
            }

            // We create a new id for this entry. We don't use
            // \Minz\Random::timebased() function because we want
            // the time part as close as possible to the reality represented
            // by the created_at value.
            $created_at = date_create_from_format('Y-m-d H:i:sP', $row['created_at']);

            // Since created_at don't contain the milliseconds part, we
            // fake it with a random value
            $milliseconds = $created_at->getTimestamp() * 1000;
            $milliseconds += random_int(0, 999);

            // Same code as timebased() function
            $time_part = $milliseconds << 20;
            $random_part = random_int(0, 1048575); // max number on 20 bits
            $new_id = strval($time_part | $random_part);

            $statement = $database->prepare(<<<'SQL'
                UPDATE users SET id = ? WHERE id = ?;
            SQL);
            $statement->execute([$new_id, $row['id']]);
        }

        $database->commit();

        return true;
    }

    public function rollback()
    {
        // do nothing, just keep the new ids if set, we can't find the old ones
        // anyway
        return true;
    }
}
