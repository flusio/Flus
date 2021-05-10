<?php

namespace flusio\migrations;

class Migration202105100001DropUsersToTopics
{
    public function migrate()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            DROP INDEX idx_users_to_topics_topic_id;
            DROP INDEX idx_users_to_topics;
            DROP TABLE users_to_topics;
        SQL);

        return true;
    }

    public function rollback()
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            CREATE TABLE users_to_topics (
                id SERIAL PRIMARY KEY,
                user_id TEXT REFERENCES users ON DELETE CASCADE ON UPDATE CASCADE,
                topic_id TEXT REFERENCES topics ON DELETE CASCADE ON UPDATE CASCADE
            );

            CREATE UNIQUE INDEX idx_users_to_topics ON users_to_topics(user_id, topic_id);
            CREATE INDEX idx_users_to_topics_topic_id ON users_to_topics(topic_id);
        SQL);

        return true;
    }
}
