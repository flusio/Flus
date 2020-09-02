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
    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'validated_at' => 'datetime',

        'validation_token' => 'string',

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

        'csrf' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * @param string $username
     * @param string $email
     * @param string $password
     */
    public static function init($username, $email, $password)
    {
        $csrf = new \Minz\CSRF();
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'username' => trim($username),
            'email' => utils\Email::sanitize($email),
            'password_hash' => $password ? password_hash($password, PASSWORD_BCRYPT) : '',
            'locale' => \flusio\utils\Locale::DEFAULT_LOCALE,
            'csrf' => $csrf->resetToken(),
        ]);
    }

    /**
     * Return the given link if attached to the current user
     *
     * @param string $id
     *
     * @return \flusio\models\Link|null
     */
    public function link($link_id)
    {
        $link_dao = new dao\Link();
        $db_link = $link_dao->findBy([
            'id' => $link_id,
            'user_id' => $this->id,
        ]);
        if ($db_link) {
            return new Link($db_link);
        } else {
            return null;
        }
    }

    /**
     * Return the given link if attached to the current user
     *
     * @param string $url
     *
     * @return \flusio\models\Link|null
     */
    public function linkByUrl($link_url)
    {
        $link_dao = new dao\Link();
        $db_link = $link_dao->findBy([
            'user_id' => $this->id,
            'url' => $link_url,
        ]);
        if ($db_link) {
            return new Link($db_link);
        } else {
            return null;
        }
    }

    /**
     * Return the given news link if attached to the current user
     *
     * @return \flusio\models\NewsLink|null
     */
    public function newsLink($news_link_id)
    {
        $news_link_dao = new dao\NewsLink();
        $db_news_link = $news_link_dao->findBy([
            'id' => $news_link_id,
            'user_id' => $this->id,
        ]);
        if ($db_news_link) {
            return new NewsLink($db_news_link);
        } else {
            return null;
        }
    }

    /**
     * Return the user' news links
     *
     * @return \flusio\models\NewsLink[]
     */
    public function newsLinks()
    {
        $news_link_dao = new dao\NewsLink();
        $db_news_links = $news_link_dao->listComputedByUserId($this->id);
        $news_links = [];
        foreach ($db_news_links as $db_news_link) {
            $news_links[] = new NewsLink($db_news_link);
        }
        return $news_links;
    }

    /**
     * Return the user' bookmarks collection
     *
     * @return \flusio\models\Collection|null
     */
    public function bookmarks()
    {
        $collection_dao = new dao\Collection();
        $db_collection = $collection_dao->findBy([
            'user_id' => $this->id,
            'type' => 'bookmarks',
        ]);
        if ($db_collection) {
            return new Collection($db_collection);
        } else {
            return null;
        }
    }

    /**
     * Return the given collection if attached to the current user
     *
     * @return \flusio\models\Collection|null
     */
    public function collection($collection_id)
    {
        $collection_dao = new dao\Collection();
        $db_collection = $collection_dao->findBy([
            'id' => $collection_id,
            'user_id' => $this->id,
            'type' => 'collection',
        ]);
        if ($db_collection) {
            return new Collection($db_collection);
        } else {
            return null;
        }
    }

    /**
     * Return the list of collections created by the user
     *
     * @param boolean $exclude_bookmarks Default is false.
     *
     * @return \flusio\models\Collection[]
     */
    public function collections($exclude_bookmarks = false)
    {
        $collection_dao = new dao\Collection();
        if ($exclude_bookmarks) {
            $db_collections = $collection_dao->listBy([
                'user_id' => $this->id,
                'type' => 'collection',
            ]);
        } else {
            $db_collections = $collection_dao->listBy([
                'user_id' => $this->id,
            ]);
        }

        $collections = [];
        foreach ($db_collections as $db_collection) {
            $collections[] = new Collection($db_collection);
        }
        return $collections;
    }

    /**
     * Return the list of collections created by the user
     *
     * @return \flusio\models\Collection[]
     */
    public function collectionsWithNumberLinks()
    {
        $collection_dao = new dao\Collection();
        $db_collections = $collection_dao->listWithNumberLinksForUser($this->id);
        $collections = [];
        foreach ($db_collections as $db_collection) {
            $collections[] = new Collection($db_collection);
        }
        return $collections;
    }

    /**
     * Return the list of collections followed by the user
     *
     * @return \flusio\models\Collection[]
     */
    public function followedCollectionsWithNumberLinks()
    {
        $collection_dao = new dao\Collection();
        $db_collections = $collection_dao->listFollowedWithNumberLinksForUser($this->id);
        $collections = [];
        foreach ($db_collections as $db_collection) {
            $collections[] = new Collection($db_collection);
        }
        return $collections;
    }

    /**
     * @param string $collection_id
     *
     * @return boolean
     *     Return true if the current user is following the given collection.
     */
    public function isFollowing($collection_id)
    {
        $followed_collection_dao = new dao\FollowedCollection();
        $followed_collection = $followed_collection_dao->findBy([
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
        $followed_collection_dao = new dao\FollowedCollection();
        return $followed_collection_dao->create([
            'created_at' => \Minz\Time::now()->format(\Minz\Model::DATETIME_FORMAT),
            'user_id' => $this->id,
            'collection_id' => $collection_id,
        ]);
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
        $followed_collection_dao = new dao\FollowedCollection();
        $followed_collection = $followed_collection_dao->findBy([
            'user_id' => $this->id,
            'collection_id' => $collection_id,
        ]);
        $followed_collection_dao->delete($followed_collection['id']);
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
        return strlen($username) <= 50;
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
            } elseif ($property === 'username') {
                $formatted_error = _('The username must be less than 50 characters.');
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
