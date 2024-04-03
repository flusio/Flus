<?php

namespace App\models;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'pocket_accounts')]
class PocketAccount
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public ?string $username;

    #[Database\Column]
    public ?string $access_token;

    #[Database\Column]
    public ?string $request_token;

    #[Database\Column]
    public ?int $error;

    #[Database\Column]
    public string $user_id;

    public function __construct(string $user_id)
    {
        $this->user_id = $user_id;
    }
}
