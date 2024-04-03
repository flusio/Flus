<?php

namespace App\migrations;

class Migration202307280001CreatePocketAccounts
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->beginTransaction();

        $database->exec(<<<'SQL'
            CREATE TABLE pocket_accounts (
                id SERIAL PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,

                username TEXT,
                request_token TEXT,
                access_token TEXT,
                error INTEGER,

                user_id TEXT NOT NULL REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE
            );
        SQL);

        $statement = $database->query(<<<'SQL'
            SELECT * FROM users
            WHERE pocket_username IS NOT NULL
            OR pocket_request_token IS NOT NULL
            OR pocket_access_token IS NOT NULL
            OR pocket_error IS NOT NULL
        SQL);

        $now = \Minz\Time::now();
        $db_users = $statement->fetchAll();

        foreach ($db_users as $db_user) {
            $sql = <<<'SQL'
                INSERT INTO pocket_accounts (
                    created_at,
                    username,
                    request_token,
                    access_token,
                    error,
                    user_id
                )
                VALUES (?, ?, ?, ?, ?, ?)
            SQL;

            $statement = $database->prepare($sql);

            $statement->execute([
                $now->format(\Minz\Database\Column::DATETIME_FORMAT),
                $db_user['pocket_username'],
                $db_user['pocket_request_token'],
                $db_user['pocket_access_token'],
                $db_user['pocket_error'],
                $db_user['id'],
            ]);
        }

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN pocket_username,
            DROP COLUMN pocket_request_token,
            DROP COLUMN pocket_access_token,
            DROP COLUMN pocket_error;
        SQL);

        $database->commit();

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->beginTransaction();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN pocket_username TEXT,
            ADD COLUMN pocket_request_token TEXT,
            ADD COLUMN pocket_access_token TEXT,
            ADD COLUMN pocket_error INTEGER;
        SQL);

        $statement = $database->query(<<<'SQL'
            SELECT * FROM pocket_accounts;
        SQL);

        $db_pocket_accounts = $statement->fetchAll();

        foreach ($db_pocket_accounts as $db_pocket_account) {
            $sql = <<<'SQL'
                UPDATE users
                SET pocket_username = :pocket_username,
                    pocket_request_token = :pocket_request_token,
                    pocket_access_token = :pocket_access_token,
                    pocket_error = :pocket_error
                WHERE id = :id
            SQL;

            $statement = $database->prepare($sql);
            $statement->execute([
                ':pocket_username' => $db_pocket_account['username'],
                ':pocket_request_token' => $db_pocket_account['request_token'],
                ':pocket_access_token' => $db_pocket_account['access_token'],
                ':pocket_error' => $db_pocket_account['error'],
                ':id' => $db_pocket_account['user_id'],
            ]);
        }

        $database->exec(<<<'SQL'
            DROP TABLE pocket_accounts;
        SQL);

        $database->commit();

        return true;
    }
}
