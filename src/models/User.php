<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * Represent a user of Flus.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'users')]
class User
{
    use dao\User;
    use Database\Recordable;
    use Validable;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public ?\DateTimeImmutable $validated_at;

    #[Database\Column]
    public ?string $validation_token;

    #[Database\Column]
    public ?string $reset_token;

    #[Database\Column]
    public ?string $subscription_account_id;

    #[Database\Column]
    public \DateTimeImmutable $subscription_expired_at;

    #[Database\Column]
    public \DateTimeImmutable $last_activity_at;

    #[Database\Column]
    public ?\DateTimeImmutable $deletion_notified_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The address email is required.'),
    )]
    #[Validable\Email(
        message: new Translatable('The address email is invalid.'),
    )]
    public string $email;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The username is required.'),
    )]
    #[Validable\Length(
        max: 50,
        message: new Translatable('The username must be less than {max} characters.'),
    )]
    #[Validable\Format(
        pattern: '/^[^@]*$/',
        message: new Translatable('The username cannot contain the character ‘@’.'),
    )]
    public string $username;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The password is required.'),
    )]
    public string $password_hash;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The locale is required.'),
    )]
    #[checks\Locale(
        message: new Translatable('The locale is invalid.'),
    )]
    public string $locale;

    #[Database\Column]
    public ?string $avatar_filename;

    #[Database\Column]
    public string $csrf;

    #[Database\Column]
    public ?string $autoload_modal;

    #[Database\Column]
    public bool $option_compact_mode;

    #[Database\Column]
    public bool $accept_contact;

    public function __construct(string $username, string $email, string $password)
    {
        $this->id = \Minz\Random::timebased();
        $this->subscription_expired_at = \Minz\Time::fromNow(1, 'month');
        $this->last_activity_at = \Minz\Time::now();
        $this->username = trim($username);
        $this->email = \Minz\Email::sanitize($email);
        $this->password_hash = self::passwordHash($password);
        $this->locale = utils\Locale::DEFAULT_LOCALE;
        $this->csrf = \Minz\Random::hex(64);
        $this->autoload_modal = '';
        $this->option_compact_mode = false;
    }

    public static function supportUser(): self
    {
        /** @var string */
        $support_email = \Minz\Configuration::$application['support_email'];
        $default_password = \Minz\Random::hex(128);

        return self::findOrCreateBy([
            'email' => \Minz\Email::sanitize($support_email),
        ], [
            'id' => \Minz\Random::timebased(),
            'username' => 'Flus',
            'password_hash' => self::passwordHash($default_password),
            'validated_at' => \Minz\Time::now(),
        ]);
    }

    public function isSupportUser(): bool
    {
        $support_email = \Minz\Configuration::$application['support_email'];
        return $this->email === $support_email;
    }

    /**
     * Return the user' bookmarks collection
     */
    public function bookmarks(): Collection
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
     */
    public function news(): Collection
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
     */
    public function readList(): Collection
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
     */
    public function neverList(): Collection
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
     * @see Link::listComputedByUserId
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'unshared'?: bool,
     *     'offset'?: int,
     *     'limit'?: int|'ALL',
     * } $options
     *
     * @return Link[]
     */
    public function links(array $selected_computed_props = [], array $options = []): array
    {
        return Link::listComputedByUserId(
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections of the user.
     *
     * @see Collection::listComputedByUserId
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'private'?: bool,
     *     'count_hidden'?: bool,
     * } $options
     *
     * @return Collection[]
     */
    public function collections(array $selected_computed_props = [], array $options = []): array
    {
        return Collection::listComputedByUserId(
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections followed by the user.
     *
     * @see Collection::listComputedFollowedByUserId
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'type'?: 'collection'|'feed'|'all',
     * } $options
     *
     * @return Collection[]
     */
    public function followedCollections(array $selected_computed_props = [], array $options = []): array
    {
        return Collection::listComputedFollowedByUserId(
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections shared to the user.
     *
     * @see Collection::listComputedSharedToUserId
     *
     * @param string[] $selected_computed_props
     * @param array{
     *     'access_type'?: 'any'|'read'|'write',
     * } $options
     *
     * @return Collection[]
     */
    public function sharedCollections(array $selected_computed_props = [], array $options = []): array
    {
        return Collection::listComputedSharedToUserId(
            $this->id,
            $selected_computed_props,
            $options
        );
    }

    /**
     * Return the collections shared by the user to the given user.
     *
     * @see Collection::listComputedSharedByUserIdTo
     *
     * @param string[] $selected_computed_props
     *
     * @return Collection[]
     */
    public function sharedCollectionsTo(string $to_user_id, array $selected_computed_props = []): array
    {
        return Collection::listComputedSharedByUserIdTo(
            $this->id,
            $to_user_id,
            $selected_computed_props
        );
    }

    /**
     * Return whether the user can write to the given collections or not.
     *
     * @param string[] $collection_ids
     */
    public function canWriteCollections(array $collection_ids): bool
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
     * Return true if the current user is following the given collection.
     */
    public function isFollowing(string $collection_id): bool
    {
        return FollowedCollection::existsBy([
            'user_id' => $this->id,
            'collection_id' => $collection_id,
        ]);
    }

    /**
     * Make the current user following the given collection.
     *
     * Be careful to check isFollowing() is returning false before calling this
     * method.
     *
     * Return the id of the created FollowedCollection.
     */
    public function follow(string $collection_id): int
    {
        $followed_collection = new FollowedCollection($this->id, $collection_id);
        $followed_collection->save();
        return $followed_collection->id;
    }

    /**
     * Make the current user unfollowing the given collection.
     */
    public function unfollow(string $collection_id): void
    {
        FollowedCollection::deleteBy([
            'user_id' => $this->id,
            'collection_id' => $collection_id,
        ]);
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
     * @param Link[] $links
     *
     * @return Link[]
     */
    public function obtainLinks(array $links): array
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
        $urls_hashes = array_map(['\App\models\Link', 'hashUrl'], $urls);
        $related_links = Link::listBy([
            'user_id' => $this->id,
            'url_hash' => $urls_hashes,
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
     * @see Link::obtainLinks
     */
    public function obtainLink(Link $link): Link
    {
        return $this->obtainLinks([$link])[0];
    }

    /**
     * Set login credentials.
     *
     * The password is not changed if empty.
     */
    public function setLoginCredentials(string $email, ?string $password = null): void
    {
        $this->email = \Minz\Email::sanitize($email);
        if ($password) {
            $this->password_hash = self::passwordHash($password);
        }
    }

    /**
     * Return true if the password matches the hash, false otherwise.
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password_hash);
    }

    /**
     * Return whether the user must validate its email or not.
     *
     * Note she has 1 day to test the application before being forced to
     * validate.
     */
    public function mustValidateEmail(): bool
    {
        return !$this->validated_at && $this->created_at < \Minz\Time::ago(1, 'day');
    }

    /**
     * Return wheter the user has a free subscription or not.
     */
    public function isSubscriptionExempted(): bool
    {
        return $this->subscription_expired_at->getTimestamp() === 0;
    }

    /**
     * Return wheter the user subscription is overdue or not.
     */
    public function isSubscriptionOverdue(): bool
    {
        return (
            !$this->isSubscriptionExempted() &&
            \Minz\Time::now() > $this->subscription_expired_at
        );
    }

    /**
     * Return whether the user should be blocked or not (email not validated or
     * subscription overdue)
     */
    public function isBlocked(): bool
    {
        $subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];
        $must_validate = $this->mustValidateEmail();
        $must_renew = $subscriptions_enabled && $this->isSubscriptionOverdue();
        return $must_validate || $must_renew;
    }

    /**
     * Change the last activity attribute. Return true if the date changed,
     * false otherwise. Only the day is remembered to not track the user too
     * much and to avoid to save the user at each request.
     */
    public function refreshLastActivity(): bool
    {
        $changed = false;
        $today = \Minz\Time::relative('today');

        if ($this->last_activity_at != $today) {
            $this->last_activity_at = $today;
            $changed = true;
        }

        if ($this->deletion_notified_at !== null) {
            $this->deletion_notified_at = null;
            $changed = true;
        }

        return $changed;
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \Minz\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:users/{$this->id}";
    }

    /**
     * Return a password hash. If password is empty, password_hash will be empty as well.
     */
    public static function passwordHash(string $password): string
    {
        return $password ? password_hash($password, PASSWORD_BCRYPT) : '';
    }
}
