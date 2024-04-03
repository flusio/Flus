<?php

namespace App\models;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'mastodon_servers')]
class MastodonServer
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $host;

    #[Database\Column]
    public string $client_id;

    #[Database\Column]
    public string $client_secret;

    public function __construct(string $host, string $client_id, string $client_secret)
    {
        $this->host = $host;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }
}
