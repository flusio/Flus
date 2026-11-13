<?php

namespace App\models;

/**
 * The owner of the feeds and of their links.
 *
 * There is only one system owner, identified by a fixed id so it can be used
 * as a constant in SQL queries.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SystemOwner extends Owner
{
    public const ID = 'system';

    public function __construct()
    {
        $this->id = self::ID;
        $this->type = 'system';
    }

    /**
     * Return the system owner and create it if it doesn't exist yet.
     */
    public static function get(): self
    {
        return self::findOrInsert(['id' => self::ID], new self());
    }
}
