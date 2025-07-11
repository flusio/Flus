<?php

namespace App\migrations;

use App\models;

class Migration202410030001AddTagsToLinks
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            ADD COLUMN tags JSON NOT NULL DEFAULT '[]';
        SQL);

        $database = \Minz\Database::get();
        $statement = $database->query(<<<'SQL'
            SELECT l.* FROM links l, messages m
            WHERE l.id = m.link_id
            AND m.content LIKE '%#%'
        SQL);

        $links = models\Link::fromDatabaseRows($statement->fetchAll());

        foreach ($links as $link) {
            $link->refreshTags();
        }

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            ALTER TABLE links
            DROP COLUMN tags;
        SQL);

        return true;
    }
}
