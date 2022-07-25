<?php

namespace flusio\models;

use flusio\utils;

/**
 * Represent a user of flusio.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class User extends \Minz\Model
{
    use DaoConnector;

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'validated_at' => 'datetime',

        'validation_token' => 'string',

        'reset_token' => 'string',

        'subscription_account_id' => [
            'type' => 'string',
        ],

        'subscription_expired_at' => [
            'type' => 'datetime',
            'required' => true,
        ],

        'email' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\User::validateEmail',
        ],

        'username' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\User::validateUsername',
        ],

        'password_hash' => [
            'type' => 'string',
            'required' => true,
        ],

        'locale' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\User::validateLocale',
        ],

        'avatar_filename' => [
            'type' => 'string',
        ],

        'csrf' => [
            'type' => 'string',
            'required' => true,
        ],

        'autoload_modal' => [
            'type' => 'string',
        ],

        'pocket_username' => [
            'type' => 'string',
        ],

        'pocket_access_token' => [
            'type' => 'string',
        ],

        'pocket_request_token' => [
            'type' => 'string',
        ],

        'pocket_error' => [
            'type' => 'integer',
        ],
    ];

    /**
     * Initialize the model with default values.
     *
     * @param mixed $values
     */
    public function __construct($values)
    {
        $expired_at = \Minz\Time::fromNow(1, 'month');
        parent::__construct(array_merge([
            'id' => utils\Random::timebased(),
            'subscription_expired_at' => $expired_at->format(\Minz\Model::DATETIME_FORMAT),
            'username' => '',
            'email' => '',
            'password_hash' => '',
            'locale' => \flusio\utils\Locale::DEFAULT_LOCALE,
            'csrf' => utils\Random::hex(64),
            'autoload_modal' => '',
        ], $values));
    }

    /**
     * @param string $username
     * @param string $email
     * @param string $password
     *
     * @return \flusio\models\User
     */
    public static function init($username, $email, $password)
    {
        return new self([
            'username' => trim($username),
            'email' => utils\Email::sanitize($email),
            'password_hash' => self::passwordHash($password),
        ]);
    }

    /**
     * @return \flusio\models\User
     */
    public static function supportUser()
    {
        $support_email = \Minz\Configuration::$application['support_email'];
        $default_password = \flusio\utils\Random::hex(128);
        return self::findOrCreateBy([
            'email' => utils\Email::sanitize($support_email),
        ], [
            'username' => 'flusio',
            'password_hash' => self::passwordHash($default_password),
            'validated_at' => \Minz\Time::now(),
        ]);
    }

    /**
     * @return boolean
     */
    public function isSupportUser()
    {
        $support_email = \Minz\Configuration::$application['support_email'];
        return $this->email === $support_email;
    }

    /**
     * Return the user' bookmarks collection
     *
     * @return \flusio\models\Collection
     */
    public function bookmarks()
    {
        $bookmarks = Collection::findBy([
            'user_id' => $this->id,
            'type' => 'bookmarks',
        ]);

        if (!$bookmarks) {
            $bookmarks = Collection::initBookmarks($this->id);
            $bookmarks->save();
        }

        return $bookmarks;
    }

    /**
     * Return the user' news collection
     *
     * @return \flusio\models\Collection
     */
    public function news()
    {
        $news = Collection::findBy([
            'user_id' => $this->id,
            'type' => 'news',
        ]);

        if (!$news) {
            $news = Collection::initNews($this->id);
            $news->save();
        }

        return $news;
    }

    /**
     * Return the user' read list collection
     *
     * @return \flusio\models\Collection
     */
    public function readList()
    {
        $read_list = Collection::findBy([
            'user_id' => $this->id,
            'type' => 'read',
        ]);

        if (!$read_list) {
            $read_list = Collection::initReadList($this->id);
            $read_list->save();
        }

        return $read_list;
    }

    /**
     * Return the user' never list collection
     *
     * @return \flusio\models\Collection
     */
    public function neverList()
    {
        $never_list = Collection::findBy([
            'user_id' => $this->id,
            'type' => 'never',
        ]);

        if (!$never_list) {
            $never_list = Collection::initNeverList($this->id);
            $never_list->save();
        }

        return $never_list;
    }

    /**
     * Return the links of the user.
     *
     * @see \flusio\models\dao\Link::listComputedByUserId
     *
     * @param string[] $selected_computed_props
     * @param array $options
     *
     * @return \flusio\models\Link[]
     */
    public function links($selected_computed_props = [], $options = [])
    {
        return Link::daoToList(
            'listComputedByUserId',
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections of the user.
     *
     * @see \flusio\models\dao\Collection::listComputedByUserId
     *
     * @param string[] $selected_computed_props
     * @param array $options
     *
     * @return \flusio\models\Collection[]
     */
    public function collections($selected_computed_props = [], $options = [])
    {
        return Collection::daoToList(
            'listComputedByUserId',
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections followed by the user.
     *
     * @see \flusio\models\dao\Collection::listComputedFollowedByUserId
     *
     * @param string[] $selected_computed_props
     * @param array $options
     *
     * @return \flusio\models\Collection[]
     */
    public function followedCollections($selected_computed_props = [], $options = [])
    {
        return Collection::daoToList(
            'listComputedFollowedByUserId',
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections shared to the user.
     *
     * @see \flusio\models\dao\Collection::listComputedSharedToUserId
     *
     * @param string[] $selected_computed_props
     * @param array $options
     *
     * @return \flusio\models\Collection[]
     */
    public function sharedCollections($selected_computed_props = [], $options = [])
    {
        return Collection::daoToList(
            'listComputedSharedToUserId',
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections shared by the user to the given user.
     *
     * @see \flusio\models\dao\Collection::listComputedSharedByUserIdTo
     *
     * @param string $to_user_id
     * @param string[] $selected_computed_props
     *
     * @return \flusio\models\Collection[]
     */
    public function sharedCollectionsTo($to_user_id, $selected_computed_props = [])
    {
        return Collection::daoToList(
            'listComputedSharedByUserIdTo',
            $this->id,
            $to_user_id,
            $selected_computed_props
        );
    }

    /**
     * Return whether the user can write to the given collections or not.
     *
     * @param string[] $collection_ids
     *
     * @return boolean
     */
    public function canWriteCollections($collection_ids)
    {
        if (empty($collection_ids)) {
            return true;
        }

        $count_owned_collections = Collection::countBy([
            'id' => $collection_ids,
            'user_id' => $this->id,
        ]);
        $count_shared_collections = CollectionShare::countBy([
            'collection_id' => $collection_ids,
            'user_id' => $this->id,
            'type' => 'write',
        ]);

        // This only works because an owned collection cannot be shared to
        // oneself, otherwise the same id could be present in both counts.
        $count_writable_collections = $count_owned_collections + $count_shared_collections;
        return $count_writable_collections === count($collection_ids);
    }

    /**
     * @param string $collection_id
     *
     * @return boolean
     *     Return true if the current user is following the given collection.
     */
    public function isFollowing($collection_id)
    {
        $followed_collection = FollowedCollection::findBy([
            'user_id' => $this->id,
            'collection_id' => $collection_id,
        ]);
        return $followed_collection !== null;
    }

    /**
     * Make the current user following the given collection.
     *
     * Be careful to check isFollowing() is returning false before calling this
     * method.
     *
     * @param string $collection_id
     *
     * @param integer The id of the create FollowedCollection object in db
     */
    public function follow($collection_id)
    {
        $followed_collection = FollowedCollection::init($this->id, $collection_id);
        return $followed_collection->save();
    }

    /**
     * Make the current user unfollowing the given collection.
     *
     * Be careful to check isFollowing() is returning true before calling this
     * method.
     *
     * @param string $collection_id
     */
    public function unfollow($collection_id)
    {
        $followed_collection = FollowedCollection::findBy([
            'user_id' => $this->id,
            'collection_id' => $collection_id,
        ]);
        FollowedCollection::delete($followed_collection->id);
    }

    /**
     * Return links owned by the user with the same URLs as the given ones.
     *
     * If a link is already owned, it's returned as it is. If a user already
     * has a link with the same URL, it's fetched from the database. Otherwise,
     * the link is copied to the user links but it's not saved in database
     * yet! (created_at will be null then)
     *
     * Order of links is not preserved!
     *
     * @param \flusio\models\Link[] $links
     *
     * @return \flusio\models\Link[]
     */
    public function obtainLinks($links)
    {
        // First, dispatch the links in two lists: owned and not owned links.
        $owned_links = [];
        $not_owned_links = [];
        foreach ($links as $link) {
            if ($this->id === $link->user_id) {
                $owned_links[] = $link;
            } else {
                $not_owned_links[] = $link;
            }
        }

        if (count($owned_links) === count($links)) {
            // All the links are owned, so we have nothing more to do
            return $links;
        }

        // Complete the owned_links list with links owned by the user, from the
        // database.
        $urls = array_column($not_owned_links, 'url');
        $urls_lookup = array_map(['\flusio\utils\Belt', 'removeScheme'], $urls);
        $related_links = Link::listBy([
            'user_id' => $this->id,
            'url_lookup' => $urls_lookup,
        ]);
        $owned_links = array_merge($owned_links, $related_links);

        if (count($owned_links) === count($links)) {
            return $owned_links;
        }

        // The last not owned links must be copied to the current user. These
        // links will not have created_at set because they are not present in
        // the database: they must be saved!
        $new_links = [];
        $related_urls = array_column($related_links, 'url');
        foreach ($not_owned_links as $link) {
            if (!in_array($link->url, $related_urls)) {
                $new_links[] = Link::copy($link, $this->id);
            }
        }

        return array_merge($owned_links, $new_links);
    }

    /**
     * Return a link owned by the user with the same URL as the given one.
     *
     * @see \flusio\models\Link::obtainLinks
     *
     * @param \flusio\models\Link $link
     *
     * @return \flusio\models\Link $link
     */
    public function obtainLink($link)
    {
        return $this->obtainLinks([$link])[0];
    }

    /**
     * Set login credentials.
     *
     * The password is not changed if empty.
     *
     * @param string $email
     * @param string $password (default is null)
     */
    public function setLoginCredentials($email, $password = null)
    {
        $this->email = utils\Email::sanitize($email);
        if ($password) {
            $this->password_hash = self::passwordHash($password);
        }
    }

    /**
     * Compare a password to the stored hash.
     *
     * @param string $password
     *
     * @return boolean Return true if the password matches the hash, else false
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Return whether the user must validate its email or not.
     *
     * Note she has 1 day to test the application before being forced to
     * validate.
     *
     * @return boolean
     */
    public function mustValidateEmail()
    {
        return !$this->validated_at && $this->created_at < \Minz\Time::ago(1, 'day');
    }

    /**
     * Return wheter the user has a free subscription or not.
     *
     * @return boolean
     */
    public function isSubscriptionExempted()
    {
        return $this->subscription_expired_at->getTimestamp() === 0;
    }

    /**
     * Return wheter the user subscription is overdue or not.
     *
     * @return boolean
     */
    public function isSubscriptionOverdue()
    {
        return (
            !$this->isSubscriptionExempted() &&
            \Minz\Time::now() > $this->subscription_expired_at
        );
    }

    /**
     * Return whether the user should be blocked or not (email not validated or
     * subscription overdue)
     *
     * @return boolean
     */
    public function isBlocked()
    {
        $subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];
        $must_validate = $this->mustValidateEmail();
        $must_renew = $subscriptions_enabled && $this->isSubscriptionOverdue();
        return $must_validate || $must_renew;
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
        return "tag:{$host},{$date}:users/{$this->id}";
    }

    /**
     * Return a password hash. If password is empty, password_hash will be empty as well.
     *
     * @param string $password
     *
     * @return string
     */
    public static function passwordHash($password)
    {
        return $password ? password_hash($password, PASSWORD_BCRYPT) : '';
    }

    /**
     * @param string $email
     * @return boolean
     */
    public static function validateEmail($email)
    {
        return utils\Email::validate($email);
    }

    /**
     * @param string $username
     * @return boolean
     */
    public static function validateUsername($username)
    {
        if (mb_strlen($username) > 50) {
            return _('The username must be less than 50 characters.');
        }

        if (utils\Belt::contains($username, '@')) {
            return _('The username cannot contain the character ‘@’.');
        }

        return true;
    }

    /**
     * @param string $locale
     * @return boolean
     */
    public static function validateLocale($locale)
    {
        $available_locales = \flusio\utils\Locale::availableLocales();
        return isset($available_locales[$locale]);
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

            if ($property === 'username' && $code === 'required') {
                $formatted_error = _('The username is required.');
            } elseif ($property === 'email' && $code === 'required') {
                $formatted_error = _('The address email is required.');
            } elseif ($property === 'email') {
                $formatted_error = _('The address email is invalid.');
            } elseif ($property === 'password_hash') {
                $formatted_error = _('The password is required.');
            } elseif ($property === 'locale' && $code === 'required') {
                $formatted_error = _('The locale is required.');
            } elseif ($property === 'locale') {
                $formatted_error = _('The locale is invalid.');
            } else {
                $formatted_error = $error['description']; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
    }
}
