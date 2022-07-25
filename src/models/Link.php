<?php

namespace flusio\models;

use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Link extends \Minz\Model
{
    use DaoConnector;
    use BulkDaoConnector;

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'locked_at' => 'datetime',

        'title' => [
            'type' => 'string',
            'required' => true,
        ],

        'url' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Link::validateUrl',
        ],

        'url_feeds' => [
            'type' => 'string',
            'required' => true,
        ],

        'is_hidden' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'reading_time' => [
            'type' => 'integer',
            'required' => true,
        ],

        'image_filename' => [
            'type' => 'string',
        ],

        'fetched_at' => [
            'type' => 'datetime',
        ],

        'fetched_code' => [
            'type' => 'integer',
        ],

        'fetched_error' => [
            'type' => 'string',
        ],

        'fetched_count' => [
            'type' => 'integer',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'feed_entry_id' => [
            'type' => 'string',
        ],

        'via_type' => [
            'type' => 'string',
        ],

        'via_resource_id' => [
            'type' => 'string',
        ],

        'via_news_type' => [
            'type' => 'string',
            'computed' => true,
        ],

        'via_news_resource_id' => [
            'type' => 'string',
            'computed' => true,
        ],

        'published_at' => [
            'type' => 'datetime',
            'computed' => true,
        ],

        'number_comments' => [
            'type' => 'integer',
            'computed' => true,
        ],

        'search_index' => [
            'type' => 'string',
        ],

        'url_lookup' => [
            'type' => 'string',
        ],
    ];

    /** @var array */
    private $cache_is_read = [];

    /**
     * @param string $url
     * @param string $user_id
     * @param boolean|string $is_hidden
     *
     * @return \flusio\models\Link
     */
    public static function init($url, $user_id, $is_hidden)
    {
        $url = \SpiderBits\Url::sanitize($url);
        return new self([
            'id' => utils\Random::timebased(),
            'title' => $url,
            'url' => $url,
            'url_feeds' => '[]',
            'is_hidden' => filter_var($is_hidden, FILTER_VALIDATE_BOOLEAN),
            'user_id' => $user_id,
            'reading_time' => 0,
            'fetched_code' => 0,
            'fetched_count' => 0,
            'via_type' => '',
        ]);
    }

    /**
     * Copy a Link to the given user.
     *
     * @param \flusio\models\Link $link
     * @param string $user_id
     *
     * @return \flusio\models\Link
     */
    public static function copy($link, $user_id)
    {
        return new self([
            'id' => utils\Random::timebased(),
            'title' => $link->title,
            'url' => $link->url,
            'url_feeds' => $link->url_feeds,
            'image_filename' => $link->image_filename,
            'is_hidden' => false,
            'reading_time' => $link->reading_time,
            'fetched_at' => $link->fetched_at,
            'fetched_code' => $link->fetched_code,
            'fetched_count' => $link->fetched_count,
            'via_type' => '',
            'user_id' => $user_id,
        ]);
    }

    /**
     * Return the owner of the link.
     *
     * @return \flusio\models\User
     */
    public function owner()
    {
        return User::find($this->user_id);
    }

    /**
     * Return the collections attached to the current link
     *
     * @return \flusio\models\Collection[]
     */
    public function collections()
    {
        return Collection::daoToList('listByLinkId', $this->id);
    }

    /**
     * Return the messages attached to the current link
     *
     * @return \flusio\models\Message[]
     */
    public function messages()
    {
        return Message::daoToList('listByLink', $this->id);
    }

    /**
     * @return \flusio\models\Collection|null
     */
    public function viaCollection()
    {
        if (
            $this->via_type !== 'collection' ||
            !$this->via_resource_id
        ) {
            return null;
        }

        return Collection::find($this->via_resource_id);
    }

    /**
     * @return \flusio\models\User|null
     */
    public function viaUser()
    {
        if (
            $this->via_type !== 'user' ||
            !$this->via_resource_id
        ) {
            return null;
        }

        return User::find($this->via_resource_id);
    }

    /**
     * Return whether or not the given user read the link URL.
     *
     * @param \flusio\models\User $user
     *
     * @return boolean
     */
    public function isReadBy($user)
    {
        if (!$user) {
            return false;
        }

        $user_id = $user->id;
        if (!isset($this->cache_is_read[$user_id])) {
            $is_read = Link::daoCall('isUrlReadByUserId', $user_id, $this->url);
            $this->cache_is_read[$user_id] = $is_read;
        }

        return $this->cache_is_read[$user_id];
    }

    /**
     * Return whether the link is shared with the given user or not (i.e. it is
     * attached to a shared collection or to an owned collection).
     *
     * @param \flusio\models\User $user
     *
     * @return boolean
     */
    public function sharedWith($user)
    {
        return (
            Collection::daoCall('existsForUserIdAndLinkId', $user->id, $this->id) ||
            CollectionShare::daoCall('existsForUserIdAndLinkId', $user->id, $this->id)
        );
    }

    /**
     * Return the list of feeds URLs if any
     *
     * @return string[]
     */
    public function feedUrls()
    {
        return json_decode($this->url_feeds, true);
    }

    /**
     * Return whether the link URL is a feed URL.
     *
     * @return boolean
     */
    public function isFeedUrl()
    {
        $feed_urls = $this->feedUrls();
        return in_array($this->url, $feed_urls);
    }

    /**
     * @return string
     */
    public function host()
    {
        return \flusio\utils\Belt::host($this->url);
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     *
     * @return string
     */
    public function tagUri()
    {
        $host = \Minz\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:links/{$this->id}";
    }

    /**
     * Return a list of errors (if any). The array keys indicated the concerned
     * property.
     *
     * @return string[]
     */
    public function validate()
    {
        $formatted_errors = [];

        foreach (parent::validate() as $property => $error) {
            $code = $error['code'];

            if ($property === 'url' && $code === 'required') {
                $formatted_error = _('The link is required.');
            } elseif ($property === 'title' && $code === 'required') {
                $formatted_error = _('The title is required.');
            } else {
                $formatted_error = $error['description'];
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
    }

    /**
     * @param string $url
     * @return boolean
     */
    public static function validateUrl($url)
    {
        $validate = filter_var($url, FILTER_VALIDATE_URL) !== false;
        if (!$validate) {
            return _('The link is invalid.'); // @codeCoverageIgnore
        }

        $parsed_url = parse_url($url);
        if ($parsed_url['scheme'] !== 'http' && $parsed_url['scheme'] !== 'https') {
            return _('Link scheme must be either http or https.');
        }

        if (isset($parsed_url['pass'])) {
            return _('The link must not include a password as it’s sensitive data.'); // @codeCoverageIgnore
        }

        return true;
    }

    /**
     * Return the list of declared properties values.
     *
     * It doesn't return the search_index and url_lookup properties which are
     * automatically generated by the database.
     *
     * @see \Minz\Model::toValues
     *
     * @return array
     */
    public function toValues()
    {
        $values = parent::toValues();
        unset($values['search_index']);
        unset($values['url_lookup']);
        return $values;
    }
}
