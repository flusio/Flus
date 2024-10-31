<?php

namespace App\migrations;

use App\models;

class Migration202410310002ChangeLinksTagsStorageFormat
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $statement = $database->query(<<<'SQL'
            SELECT l.* FROM links l
            WHERE jsonb_typeof(l.tags) = 'array'
            AND jsonb_array_length(l.tags) > 0;
        SQL);
        $db_links = $statement->fetchAll();
        $links = models\Link::fromDatabaseRows($db_links);

        $database->beginTransaction();

        foreach ($links as $link) {
            $tags = $link->tags;
            $link->setTags($tags);
            $link->save();
        }

        $database->commit();

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $statement = $database->query(<<<'SQL'
            SELECT l.* FROM links l
            WHERE jsonb_typeof(l.tags) = 'object';
        SQL);
        $db_links = $statement->fetchAll();
        $links = models\Link::fromDatabaseRows($db_links);

        $database->beginTransaction();

        foreach ($links as $link) {
            $link->tags = array_values($link->tags);
            $link->save();
        }

        $database->commit();

        return true;
    }
}
