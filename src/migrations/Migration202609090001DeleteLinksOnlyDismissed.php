<?php

namespace App\migrations;

class Migration202609090001DeleteLinksOnlyDismissed
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        // Dismissing a link no longer copies it to the user: delete the links
        // that were only copied for this purpose, i.e. attached only to the
        // "never" collection, without notes, and not read or to read later.
        $database->exec(<<<'SQL'
            DELETE FROM links l
            WHERE EXISTS (
                SELECT 1
                FROM links_to_collections lc, collections c
                WHERE lc.link_id = l.id
                AND lc.collection_id = c.id
                AND c.type = 'never'
            )
            AND NOT EXISTS (
                SELECT 1
                FROM links_to_collections lc, collections c
                WHERE lc.link_id = l.id
                AND lc.collection_id = c.id
                AND c.type <> 'never'
            )
            AND NOT EXISTS (
                SELECT 1
                FROM notes n
                WHERE n.link_id = l.id
            )
            AND NOT EXISTS (
                SELECT 1
                FROM url_statuses us
                WHERE us.user_id = l.user_id
                AND us.url_hash = l.url_hash
                AND (us.read_at IS NOT NULL OR us.read_later_at IS NOT NULL)
            );
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        // do nothing on purpose
        return true;
    }
}
