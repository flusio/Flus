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

    public const VALID_TYPES = ['bookmarks', 'collection', 'feed'];

    public const NAME_MAX_LENGTH = 100;

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

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

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],

        'group_id' => [
            'type' => 'string',
        ],

        'number_links' => [
            'type' => 'integer',
            'computed' => true,
        ],

        'feed_url' => [
            'type' => 'string',
        ],

        'feed_site_url' => [
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
    public static function initFeed($user_id, $feed_url)
    {
        $feed_url = \SpiderBits\Url::sanitize($feed_url);
        return new self([
            'name' => substr($feed_url, 0, self::NAME_MAX_LENGTH),
            'feed_url' => $feed_url,
            'type' => 'feed',
            'user_id' => $user_id,
            'is_public' => true,
        ]);
    }

    /**
     * Return the name of the collection.
     *
     * If the collection is of "bookmarks" type, the localized version is
     * returned.
     *
     * @return string
     */
    public function name()
    {
        if ($this->type === 'bookmarks') {
            return _('Bookmarks');
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
     * Return the list of links attached to this collection.
     *
     * You can pass an offset and a limit to paginate the results. It is not
     * paginated by default.
     *
     * @param integer $offset
     * @param integer|string $limit
     *
     * @return \flusio\models\Link[]
     */
    public function links($offset = 0, $limit = 'ALL')
    {
        return Link::daoToList(
            'listByCollectionIdWithNumberComments',
            $this->id,
            false,
            $offset,
            $limit
        );
    }

    /**
     * Return the list of not hidden links attached to this collection
     *
     * You can pass an offset and a limit to paginate the results. It is not
     * paginated by default.
     *
     * @param integer $offset
     * @param integer|string $limit
     *
     * @return \flusio\models\Link[]
     */
    public function visibleLinks($offset = 0, $limit = 'ALL')
    {
        return Link::daoToList(
            'listByCollectionIdWithNumberComments',
            $this->id,
            true,
            $offset,
            $limit
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
        $group_id = null;
        if ($this->user_id === $user_id) {
            $group_id = $this->group_id;
        } else {
            $followed_collection_dao = new dao\FollowedCollection();
            $db_followed_collection = $followed_collection_dao->findBy([
                'user_id' => $user_id,
                'collection_id' => $this->id,
            ]);
            if ($db_followed_collection) {
                $group_id = $db_followed_collection['group_id'];
            }
        }

        return Group::find($group_id);
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
     * Sort collections based on given locale
     *
     * @param \flusio\models\Collection[] $collections
     * @param string $locale
     */
    public static function sort(&$collections, $locale)
    {
        $collator = new \Collator($locale);
        usort($collections, function ($collection1, $collection2) use ($collator) {
            if ($collection1->type === 'bookmarks') {
                return -1;
            }

            if ($collection2->type === 'bookmarks') {
                return 1;
            }

            return $collator->compare($collection1->name, $collection2->name);
        });
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
