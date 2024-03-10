<?php

namespace flusio\migrations;

class Migration202403100002SetAutoloadModalToShowcaseContact
{
    public function migrate(): bool
    {
        $database = \Minz\Database::get();

        $database->exec(<<<'SQL'
            UPDATE users SET autoload_modal = 'showcase contact';
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
