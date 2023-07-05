<?php

namespace flusio\migrations;

class Migration202111040002RemoveAtFromUsersUsername
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $statement = $database->query(<<<'SQL'
            SELECT id, username
            FROM users
            WHERE username LIKE '%@%'
        SQL);
        $db_users = $statement->fetchAll();

        foreach ($db_users as $db_user) {
            $result = preg_match('/(.*)@(.*)/', $db_user['username'], $matches);

            if (!$result) {
                continue;
            }

            if (!empty($matches[1])) {
                $new_username = $matches[1];
            } else {
                $new_username = $matches[2];
            }

            $statement = $database->prepare(<<<'SQL'
                UPDATE users
                SET username = :username
                WHERE id = :id
            SQL);

            $statement->execute([
                ':id' => $db_user['id'],
                ':username' => $new_username,
            ]);
        }

        return true;
    }

    public function rollback(): bool
    {
        // Do nothing on purpose
        return true;
    }
}
