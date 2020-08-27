<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class NewsLink extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'integer',
        ],

        'created_at' => [
            'type' => 'datetime',
        ],

        'title' => [
            'type' => 'string',
            'required' => true,
        ],

        'url' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Link::validateUrl',
        ],

        'reading_time' => [
            'type' => 'integer',
            'required' => true,
        ],

        'is_hidden' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * @param \flusio\models\Link $link
     * @param string $user_id
     *
     * @return \flusio\models\NewsLink
     */
    public static function initFromLink($link, $user_id)
    {
        return new self([
            'title' => $link->title,
            'url' => $link->url,
            'reading_time' => $link->reading_time,
            'user_id' => $user_id,
            'is_hidden' => false,
        ]);
    }

    /**
     * @return string
     */
    public function host()
    {
        $parsed_url = parse_url($this->url);
        $host = idn_to_utf8($parsed_url['host'], IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if (\flusio\utils\Belt::startsWith($host, 'www.')) {
            return substr($host, 4);
        } else {
            return $host;
        }
    }
}
