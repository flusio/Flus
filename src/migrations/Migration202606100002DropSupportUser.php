<?php

namespace App\migrations;

use App\models;

class Migration202606100002DropSupportUser
{
    public function migrate(): bool
    {
        $support_email = \App\Configuration::$application['support_email'];

        $database = \Minz\Database::get();

        $statement = $database->prepare(<<<'SQL'
            UPDATE links SET user_id = NULL WHERE user_id = (
                SELECT id FROM users WHERE email = :support_email
            );
        SQL);

        $statement->execute([
            ':support_email' => $support_email,
        ]);

        $statement = $database->prepare(<<<'SQL'
            UPDATE collections SET user_id = NULL WHERE user_id = (
                SELECT id FROM users WHERE email = :support_email
            );
        SQL);

        $statement->execute([
            ':support_email' => $support_email,
        ]);

        $statement = $database->prepare(<<<'SQL'
            DELETE FROM users WHERE email = :support_email;
        SQL);

        $statement->execute([
            ':support_email' => $support_email,
        ]);

        return true;
    }

    public function rollback(): bool
    {
        $now = \Minz\Time::now();
        $support_id = \Minz\Random::timebased();
        $support_email = \App\Configuration::$application['support_email'];
        $support_username = 'Flus';
        $support_password_hash = models\User::passwordHash(\Minz\Random::hex(128));

        $database = \Minz\Database::get();

        $statement = $database->prepare(<<<'SQL'
            INSERT INTO users (id, created_at, email, username, password_hash, validated_at)
            VALUES (:id, :now, :email, :username, :password_hash, :now);
        SQL);

        $statement->execute([
            ':id' => $support_id,
            ':email' => $support_email,
            ':username' => $support_username,
            ':password_hash' => $support_password_hash,
            ':now' => $now->format(\Minz\Database\Column::DATETIME_FORMAT),
        ]);

        $statement = $database->prepare(<<<'SQL'
            UPDATE links SET user_id = (
                SELECT id FROM users WHERE email = :email
            );
        SQL);

        $statement->execute([
            ':email' => $support_email,
        ]);

        $statement = $database->prepare(<<<'SQL'
            UPDATE collections SET user_id = (
                SELECT id FROM users WHERE email = :email
            );
        SQL);

        $statement->execute([
            ':email' => $support_email,
        ]);

        return true;
    }
}
