<?php

namespace flusio\models;

use flusio\utils;

/**
 * Represent a list containing a set of links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends \Minz\Model
{
    use DaoConnector;
    use BulkDaoConnector;

    public const VALID_TYPES = ['bookmarks', 'news', 'read', 'never', 'collection', 'feed'];

    public const NAME_MAX_LENGTH = 100;

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'locked_at' => 'datetime',

        'name' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Collection::validateName',
        ],

        'description' => [
            'type' => 'string',
        ],

        'type' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Collection::validateType',
        ],

        'is_public' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'image_filename' => [
            'type' => 'string',
        ],

        'image_fetched_at' => [
            'type' => 'datetime',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'group_id' => [
            'type' => 'string',
        ],

        'feed_url' => [
            'type' => 'string',
        ],

        'feed_type' => [
            'type' => 'string',
        ],

        'feed_site_url' => [
            'type' => 'string',
        ],

        'feed_last_hash' => [
            'type' => 'string',
        ],

        'feed_fetched_code' => [
            'type' => 'integer',
        ],

        'feed_fetched_at' => [
            'type' => 'datetime',
        ],

        'feed_fetched_error' => [
            'type' => 'string',
        ],

        'number_links' => [
            'type' => 'integer',
            'computed' => true,
        ],

        'time_filter' => [
            'type' => 'string',
            'computed' => true,
        ],
    ];

    /**
     * Initialize the model with default values.
     *
     * @param mixed $values
     */
    public function __construct($values)
    {
        parent::__construct(array_merge([
            'id' => utils\Random::timebased(),
            'description' => '',
            'type' => 'collection',
            'is_public' => false,
            'feed_fetched_code' => 0,
        ], $values));
    }

    /**
     * @param string $user_id
     * @param string $name
     * @param string $description
     * @param boolean|string $is_public
     *
     * @return \flusio\models\Collection
     */
    public static function init($user_id, $name, $description, $is_public)
    {
        return new self([
            'name' => trim($name),
            'description' => trim($description),
            'is_public' => filter_var($is_public, FILTER_VALIDATE_BOOLEAN),
            'user_id' => $user_id,
        ]);
    }

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Collection
     */
    public static function initBookmarks($user_id)
    {
        return new self([
            'name' => _('Bookmarks'),
            'type' => 'bookmarks',
            'user_id' => $user_id,
        ]);
    }

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Collection
     */
    public static function initReadList($user_id)
    {
        return new self([
            'name' => _('Links read'),
            'type' => 'read',
            'user_id' => $user_id,
        ]);
    }

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Collection
     */
    public static function initNeverList($user_id)
    {
        return new self([
            'name' => _('Links never to read'),
            'type' => 'never',
            'user_id' => $user_id,
        ]);
    }

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Collection
     */
    public static function initNews($user_id)
    {
        return new self([
            'name' => _('News'),
            'type' => 'news',
            'user_id' => $user_id,
        ]);
    }

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Collection
     */
    public static function initFeed($user_id, $feed_url)
    {
        $feed_url = \SpiderBits\Url::sanitize($feed_url);
        return new self([
            'name' => utils\Belt::cut($feed_url, self::NAME_MAX_LENGTH),
            'feed_url' => $feed_url,
            'type' => 'feed',
            'user_id' => $user_id,
            'is_public' => true,
        ]);
    }

    /**
     * Return the name of the collection.
     *
     * If the collection is one of bookmarks, read or news types, the localized
     * version is returned.
     *
     * @return string
     */
    public function name()
    {
        if ($this->type === 'bookmarks') {
            return _('Bookmarks');
        } elseif ($this->type === 'read') {
            return _('Links read');
        } elseif ($this->type === 'news') {
            return _('News');
        } else {
            return $this->name;
        }
    }

    /**
     * Return the owner of the collection.
     *
     * @return \flusio\models\User
     */
    public function owner()
    {
        return User::find($this->user_id);
    }

    /**
     * Return links of the current collection.
     *
     * @see \flusio\models\dao\Link::listComputedByCollectionId
     *
     * @param string[] $selected_computed_props
     * @param array $options
     *
     * @return \flusio\models\Link[]
     */
    public function links($selected_computed_props = [], $options = [])
    {
        return Link::daoToList(
            'listComputedByCollectionId',
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return a link from this collection with the given URL and not owned by
     * the given user.
     *
     * @param string $user_id
     * @param string $url_lookup
     *
     * @return \flusio\models\Link|null
     */
    public function linkNotOwnedByUrl($user_id, $url_lookup)
    {
        return Link::daoToModel(
            'findNotOwnedByCollectionIdAndUrl',
            $user_id,
            $this->id,
            $url_lookup,
        );
    }

    /**
     * Return the group if any for a given user.
     *
     * If the collection is owned by the user, the group is the one directly
     * attached to the current collection. Otherwise, the group is the one
     * attached to a corresponding FollowedCollection.
     *
     * @param string $user_id
     *
     * @return \flusio\models\Group|null
     */
    public function groupForUser($user_id)
    {
        if ($this->user_id === $user_id) {
            return Group::find($this->group_id);
        } else {
            $followed_collection = FollowedCollection::findBy([
                'user_id' => $user_id,
                'collection_id' => $this->id,
            ]);
            if ($followed_collection) {
                return Group::find($followed_collection->group_id);
            }
        }

        return null;
    }

    /**
     * Return the topics attached to the current collection
     *
     * @return \flusio\models\Topic[]
     */
    public function topics()
    {
        return Topic::daoToList('listByCollectionId', $this->id);
    }

    /**
     * Return the CollectionShares attached to the current collection
     *
     * @return \flusio\models\CollectionShare[]
     */
    public function shares()
    {
        $collection_shares = CollectionShare::daoToList(
            'listComputedByCollectionId',
            $this->id,
            ['username']
        );
        utils\Sorter::localeSort($collection_shares, 'username');
        return $collection_shares;
    }

    /**
     * Return whether the collections is shared with the given user.
     *
     * If $access_type is 'any' or 'read', the method returns true just if a
     * collection_share exists for this collection and user.
     *
     * If $access_type is 'write', the method will check that the collection
     * share has a 'write' type.
     *
     * @param \flusio\models\User $user
     * @param string $access_type
     *
     * @return boolean
     */
    public function sharedWith($user, $access_type = 'any')
    {
        $existing_collection_share = CollectionShare::findBy([
            'collection_id' => $this->id,
            'user_id' => $user->id,
        ]);

        if (!$existing_collection_share) {
            return false;
        }

        return (
            $access_type === 'any' ||
            $access_type === 'read' ||
            $access_type === $existing_collection_share->type
        );
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
        return "tag:{$host},{$date}:collections/{$this->id}";
    }

    /**
     * Return the feed site URL to be displayed
     *
     * @return string
     */
    public function feedWebsite()
    {
        if ($this->feed_site_url) {
            return \flusio\utils\Belt::host($this->feed_site_url);
        } else {
            return \flusio\utils\Belt::host($this->feed_url);
        }
    }

    /**
     * Return the description as HTML (from Markdown).
     *
     * @return string
     */
    public function descriptionAsHtml()
    {
        $markdown = new utils\MiniMarkdown();
        return $markdown->text($this->description);
    }

    /**
     * @param string $type
     * @return boolean
     */
    public static function validateType($type)
    {
        return in_array($type, self::VALID_TYPES);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public static function validateName($name)
    {
        return mb_strlen($name) <= self::NAME_MAX_LENGTH;
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

            if ($property === 'name' && $code === 'required') {
                $formatted_error = _('The name is required.');
            } elseif ($property === 'name') {
                $formatted_error = _('The name must be less than 100 characters.');
            } else {
                $formatted_error = $error['description']; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
    }
}
