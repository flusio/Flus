<?php

namespace App\migrations;

use App\models;

class Migration202007150001AddCsrfToUsers
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN csrf TEXT NOT NULL DEFAULT '';
        SQL);

        $users = models\User::listAll();
        foreach ($users as $user) {
            models\User::update($user->id, [
                'csrf' => \bin2hex(\random_bytes(32)),
            ]);
        }

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN csrf;
        SQL);

        return true;
    }
}
