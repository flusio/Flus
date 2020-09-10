<?php

namespace flusio\migrations;

class Migration202007150001AddCsrfToUsers
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            ADD COLUMN csrf TEXT NOT NULL DEFAULT '';
        SQL);

        $user_dao = new \flusio\models\dao\User();
        $db_users = $user_dao->listAll();
        foreach ($db_users as $db_user) {
            $user_dao->update($db_user['id'], [
                'csrf' => \bin2hex(\random_bytes(32)),
            ]);
        }

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE users
            DROP COLUMN csrf;
        SQL);

        return true;
    }
}
