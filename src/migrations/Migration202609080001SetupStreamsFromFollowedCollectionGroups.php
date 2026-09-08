<?php

namespace App\migrations;

class Migration202609080001SetupStreamsFromFollowedCollectionGroups
{
    /**
     * Create streams from followed_collections.group.
     *
     * The streams are not created for alpha users as they have already been set
     * up.
     */
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        // Load the followed collections attached to a group, excluding alpha
        // users.
        $statement = $database->query(<<<'SQL'
            SELECT fc.id, fc.user_id, g.name AS group_name
            FROM followed_collections fc

            INNER JOIN groups g ON g.id = fc.group_id

            WHERE NOT EXISTS (
                SELECT 1
                FROM feature_flags ff
                WHERE ff.type = 'alpha'
                AND ff.user_id = fc.user_id
            )
        SQL);

        /**
         * @var array<array{
         *     id: int,
         *     user_id: string,
         *     group_name: string,
         * }>
         */
        $follows = $statement->fetchAll();

        $statement_find_stream = $database->prepare(<<<'SQL'
            SELECT id FROM streams
            WHERE user_id = :user_id
            AND name = :name
        SQL);

        $statement_create_stream = $database->prepare(<<<'SQL'
            INSERT INTO streams (id, created_at, name, user_id)
            VALUES (:id, :created_at, :name, :user_id)
        SQL);

        $statement_attach_follow = $database->prepare(<<<'SQL'
            INSERT INTO streams_to_follows (created_at, stream_id, follow_id)
            VALUES (:created_at, :stream_id, :follow_id)
            ON CONFLICT (stream_id, follow_id) DO NOTHING
        SQL);

        $now = \Minz\Time::now()->format(\Minz\Database\Column::DATETIME_FORMAT);

        $stream_ids_cache = [];

        foreach ($follows as $follow) {
            $stream_id_key = "{$follow['user_id']}#{$follow['group_name']}";

            if (isset($stream_ids_cache[$stream_id_key])) {
                $stream_id = $stream_ids_cache[$stream_id_key];
            } else {
                $statement_find_stream->execute([
                    ':user_id' => $follow['user_id'],
                    ':name' => $follow['group_name'],
                ]);

                $stream_id = $statement_find_stream->fetchColumn();

                if (!$stream_id) {
                    $stream_id = \Minz\Random::timebased();

                    $statement_create_stream->execute([
                        ':id' => $stream_id,
                        ':created_at' => $now,
                        ':name' => $follow['group_name'],
                        ':user_id' => $follow['user_id'],
                    ]);
                }

                $stream_ids_cache[$stream_id_key] = $stream_id;
            }

            $statement_attach_follow->execute([
                ':created_at' => $now,
                ':stream_id' => $stream_id,
                ':follow_id' => $follow['id'],
            ]);
        }

        return true;
    }

    /**
     * Recreate the groups from the streams.
     *
     * A followed collection can be in several streams while it can only be in
     * one group: only the first stream is turned into a group.
     */
    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $statement = $database->query(<<<'SQL'
            SELECT sf.follow_id, fc.user_id, s.name AS stream_name
            FROM streams_to_follows sf

            INNER JOIN streams s ON s.id = sf.stream_id
            INNER JOIN followed_collections fc ON fc.id = sf.follow_id

            ORDER BY sf.follow_id, s.created_at, s.id
        SQL);

        /**
         * @var array<array{
         *     follow_id: int,
         *      user_id: string,
         *      stream_name: string,
         * }>
         */
        $stream_follows = $statement->fetchAll();

        $statement_find_group = $database->prepare(<<<'SQL'
            SELECT id FROM groups
            WHERE user_id = :user_id
            AND name = :name
        SQL);

        $statement_create_group = $database->prepare(<<<'SQL'
            INSERT INTO groups (id, created_at, name, user_id)
            VALUES (:id, :created_at, :name, :user_id)
        SQL);

        $statement_attach_group = $database->prepare(<<<'SQL'
            UPDATE followed_collections
            SET group_id = :group_id
            WHERE id = :id
        SQL);

        $now = \Minz\Time::now()->format(\Minz\Database\Column::DATETIME_FORMAT);

        $group_ids_cache = [];
        $processed_follow_ids = [];

        foreach ($stream_follows as $stream_follow) {
            if (isset($processed_follow_ids[$stream_follow['follow_id']])) {
                continue;
            }

            $processed_follow_ids[$stream_follow['follow_id']] = true;

            $group_id_key = "{$stream_follow['user_id']}#{$stream_follow['stream_name']}";

            if (isset($group_ids_cache[$group_id_key])) {
                $group_id = $group_ids_cache[$group_id_key];
            } else {
                $statement_find_group->execute([
                    ':user_id' => $stream_follow['user_id'],
                    ':name' => $stream_follow['stream_name'],
                ]);

                $group_id = $statement_find_group->fetchColumn();

                if (!$group_id) {
                    $group_id = \Minz\Random::timebased();

                    $statement_create_group->execute([
                        ':id' => $group_id,
                        ':created_at' => $now,
                        ':name' => $stream_follow['stream_name'],
                        ':user_id' => $stream_follow['user_id'],
                    ]);
                }

                $group_ids_cache[$group_id_key] = $group_id;
            }

            $statement_attach_group->execute([
                ':group_id' => $group_id,
                ':id' => $stream_follow['follow_id'],
            ]);
        }

        return true;
    }
}
