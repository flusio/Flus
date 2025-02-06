<?php

namespace App\http;

use Minz\Database;
use SpiderBits\Response;

/**
 * Represent a HTTP cache entry.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'cache_entries')]
class CacheEntry
{
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public \DateTimeImmutable $expired_at;

    #[Database\Column]
    public string $key;

    #[Database\Column]
    public string $url;

    #[Database\Column]
    public string $response_path;

    public function __construct(string $key, string $url, \DateTimeImmutable $expired_at)
    {
        $this->expired_at = $expired_at;
        $this->key = $key;
        $this->url = $url;
        $this->response_path = $key;
    }

    public static function findByKey(string $key): ?self
    {
        return self::findBy(['key' => $key]);
    }

    public function hasExpired(): bool
    {
        return $this->expired_at >= \Minz\Time::now();
    }
}
