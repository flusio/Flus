<?php

namespace flusio\migrations;

class Migration202209220001SetAutoloadModalToShowcaseLink
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE users SET autoload_modal = 'showcase link';
        SQL);

        return true;
    }

    public function rollback(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE users SET autoload_modal = '';
        SQL);

        return true;
    }
}
