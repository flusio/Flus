<?php

namespace App\migrations;

class Migration202009090001CreateTopics
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE topics (
                id TEXT PRIMARY KEY,
                created_at TIMESTAMPTZ NOT NULL,
                label TEXT NOT NULL
            );

            CREATE TABLE collections_to_topics (
                id SERIAL PRIMARY KEY,
                collection_id TEXT REFERENCES collections ON DELETE CASCADE ON UPDATE CASCADE,
                topic_id TEXT REFERENCES topics ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_collections_to_topics ON collections_to_topics(collection_id, topic_id);
            CREATE INDEX idx_collections_to_topics_topic_id ON collections_to_topics(topic_id);
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_collections_to_topics_topic_id;
            DROP INDEX idx_collections_to_topics;
            DROP TABLE collections_to_topics;
            DROP TABLE topics;
        SQL);

        return true;
    }
}
