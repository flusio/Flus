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

        'type' => [
            'type' => 'string',
            'required' => true,
        ],

        'ip' => [
            'type' => 'string',
        ],
    ];

    /**
     * Create a log in DB for the given URL.
     *
     * @param string $url
     * @param string $type
     * @param string $ip (optional)
     */
    public static function log($url, $type, $ip = null)
    {
        $host = \flusio\utils\Belt::host($url);
        $fetch_log = new self([
            'url' => $url,
            'host' => $host,
            'type' => $type,
            'ip' => $ip,
        ]);
        $fetch_log->save();
    }

    /**
     * Determine if we reached the rate limit for the URL host.
     *
     * @param string $url
     * @param string $type
     * @param string $ip (optional)
     *
     * @return boolean
     */
    public static function hasReachedRateLimit($url, $type, $ip = null)
    {
        $host = \flusio\utils\Belt::host($url);
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

        $count = self::daoCall('countFetchesToHost', $host, $since, $type, $ip);
        return $count >= $count_limit;
    }

    /**
     * Return the list of declared properties values.
     *
     * It doesn't return the id property because it is automatically generated
     * by the database.
     *
     * @see \Minz\Model::toValues
     *
     * @return array
     */
    public function toValues()
    {
        $values = parent::toValues();
        unset($values['id']);
        return $values;
    }
}
