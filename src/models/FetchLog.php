<?php

namespace flusio\models;

use flusio\utils;
use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'fetch_logs')]
class FetchLog
{
    use dao\FetchLog;
    use Database\Recordable;

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $url;

    #[Database\Column]
    public string $host;

    #[Database\Column]
    public string $type;

    #[Database\Column]
    public ?string $ip;

    /**
     * Create a log in DB for the given URL.
     */
    public static function log(string $url, string $type, ?string $ip = null)
    {
        $fetch_log = new self();

        $fetch_log->url = $url;
        $fetch_log->host = utils\Belt::host($url);
        $fetch_log->type = $type;
        $fetch_log->ip = $ip;

        $fetch_log->save();
    }

    /**
     * Determine if we reached the rate limit for the URL host.
     */
    public static function hasReachedRateLimit(string $url, string $type, ?string $ip = null): bool
    {
        $host = utils\Belt::host($url);
        $since = \Minz\Time::ago(1, 'minute');

        // Most of the time, we rate limit the requests to 25 requests per
        // minute. But we must be more drastic with Youtube servers which
        // require a limit of 1 req/min for the links. The limit for the feeds
        // seems to be higher, but I didn't succeed to find the exact count.
        if ($host === 'youtube.com' && $type === 'link') {
            $count_limit = 1;
        } elseif ($host === 'youtube.com' && $type === 'feed') {
            $count_limit = 10;
        } else {
            $type = null;
            $count_limit = 25;
        }

        $count = self::countFetchesToHost($host, $since, $type, $ip);

        return $count >= $count_limit;
    }
}
