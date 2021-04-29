<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FetchLog extends \Minz\Model
{
    use DaoConnector;

    public const PROPERTIES = [
        'id' => [
            'type' => 'integer',
        ],

        'created_at' => [
            'type' => 'datetime',
        ],

        'url' => [
            'type' => 'string',
            'required' => true,
        ],

        'host' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * Create a log in DB for the given URL.
     *
     * @param string $url
     */
    public static function log($url)
    {
        $host = \flusio\utils\Belt::host($url);
        $fetch_log = new self([
            'url' => $url,
            'host' => $host,
        ]);
        $fetch_log->save();
    }

    /**
     * Determine if we reached the rate limit for the URL host.
     *
     * It returns true if there were more than 25 requests to the host within
     * 1 minute.
     *
     * @param string $url
     *
     * @return boolean
     */
    public static function hasReachedRateLimit($url)
    {
        $host = \flusio\utils\Belt::host($url);
        $since = \Minz\Time::ago(1, 'minute');
        $count_limit = 25;
        $count = self::daoCall('countFetchesToHost', $host, $since);
        return $count >= $count_limit;
    }
}
