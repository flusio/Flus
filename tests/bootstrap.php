<?php

$app_path = realpath(__DIR__ . '/..');

include $app_path . '/autoload.php';

\Minz\Configuration::load('test', $app_path);
\Minz\Environment::initialize();
\Minz\Environment::startSession();

$faker = \Faker\Factory::create();

$faker_seed = getenv('SEED');
if ($faker_seed) {
    $faker_seed = intval($faker_seed);
} else {
    $faker_seed = random_int(PHP_INT_MIN, PHP_INT_MAX);
}

$faker->seed($faker_seed);
echo 'Use SEED=' . $faker_seed . " to reproduce this suite.\n";

// Initialize the factories
\Minz\Tests\DatabaseFactory::addFactory(
    'user',
    '\flusio\models\dao\User',
    [
        'id' => function () {
            return \flusio\utils\Random::hex(32);
        },
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'validated_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'subscription_expired_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'username' => function () use ($faker) {
            return $faker->name;
        },
        'email' => function () use ($faker) {
            return $faker->email;
        },
        'password_hash' => function () use ($faker) {
            return password_hash($faker->password, PASSWORD_BCRYPT);
        },
        'locale' => function () {
            // Force the value to facilitate the tests (i.e. we would have to
            // localize the tests as well, which would be painful)
            return 'en_GB';
        },
        'csrf' => function () {
            return \flusio\utils\Random::hex(64);
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'token',
    '\flusio\models\dao\Token',
    [
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'token' => function () {
            return \flusio\utils\Random::hex(32);
        },
        'expired_at' => function () use ($faker) {
            return $faker->iso8601;
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'session',
    '\flusio\models\dao\Session',
    [
        'id' => function () {
            return \flusio\utils\Random::hex(32);
        },
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'name' => function () use ($faker) {
            return "{$faker->word} on {$faker->word}";
        },
        'ip' => function () use ($faker) {
            return $faker->ipv6;
        },
        'user_id' => function () {
            $user_factory = new \Minz\Tests\DatabaseFactory('user');
            return $user_factory->create();
        },
        'token' => function () {
            $token_factory = new \Minz\Tests\DatabaseFactory('token');
            return $token_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'collection',
    '\flusio\models\dao\Collection',
    [
        'id' => function () {
            return \flusio\utils\Random::timebased();
        },
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'name' => function () use ($faker) {
            return $faker->words(3, true);
        },
        'type' => function () use ($faker) {
            return $faker->randomElement(\flusio\models\Collection::VALID_TYPES);
        },
        'is_public' => function () use ($faker) {
            return (int)$faker->boolean;
        },
        'user_id' => function () use ($faker) {
            $user_factory = new \Minz\Tests\DatabaseFactory('user');
            return $user_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'link',
    '\flusio\models\dao\Link',
    [
        'id' => function () {
            return \flusio\utils\Random::timebased();
        },
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'title' => function () use ($faker) {
            return $faker->words(3, true);
        },
        'url' => function () use ($faker) {
            return $faker->url;
        },
        'is_public' => function () use ($faker) {
            return (int)$faker->boolean;
        },
        'user_id' => function () use ($faker) {
            $user_factory = new \Minz\Tests\DatabaseFactory('user');
            return $user_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'link_to_collection',
    '\flusio\models\dao\LinksToCollections',
    [
        'link_id' => function () {
            $link_factory = new \Minz\Tests\DatabaseFactory('link');
            return $link_factory->create();
        },
        'collection_id' => function () {
            $collection_factory = new \Minz\Tests\DatabaseFactory('collection');
            return $collection_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'followed_collection',
    '\flusio\models\dao\FollowedCollection',
    [
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'user_id' => function () {
            $user_factory = new \Minz\Tests\DatabaseFactory('user');
            return $user_factory->create();
        },
        'collection_id' => function () {
            $collection_factory = new \Minz\Tests\DatabaseFactory('collection');
            return $collection_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'news_link',
    '\flusio\models\dao\NewsLink',
    [
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'title' => function () use ($faker) {
            return $faker->words(3, true);
        },
        'url' => function () use ($faker) {
            return $faker->url;
        },
        'reading_time' => function () use ($faker) {
            return $faker->randomDigit;
        },
        'is_read' => function () use ($faker) {
            return (int)$faker->boolean;
        },
        'is_removed' => function () use ($faker) {
            return (int)$faker->boolean;
        },
        'user_id' => function () use ($faker) {
            $user_factory = new \Minz\Tests\DatabaseFactory('user');
            return $user_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'message',
    '\flusio\models\dao\Message',
    [
        'id' => function () {
            return \flusio\utils\Random::hex(32);
        },
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'content' => function () use ($faker) {
            return $faker->paragraphs(3, true);
        },
        'link_id' => function () use ($faker) {
            $link_factory = new \Minz\Tests\DatabaseFactory('link');
            return $link_factory->create();
        },
        'user_id' => function () use ($faker) {
            $user_factory = new \Minz\Tests\DatabaseFactory('user');
            return $user_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'topic',
    '\flusio\models\dao\Topic',
    [
        'id' => function () {
            return \flusio\utils\Random::hex(32);
        },
        'created_at' => function () use ($faker) {
            return $faker->iso8601;
        },
        'label' => function () use ($faker) {
            return $faker->word;
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'collection_to_topic',
    '\flusio\models\dao\CollectionsToTopics',
    [
        'collection_id' => function () {
            $collection_factory = new \Minz\Tests\DatabaseFactory('collection');
            return $collection_factory->create();
        },
        'topic_id' => function () {
            $topic_factory = new \Minz\Tests\DatabaseFactory('topic');
            return $topic_factory->create();
        },
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'user_to_topic',
    '\flusio\models\dao\UsersToTopics',
    [
        'user_id' => function () {
            $user_factory = new \Minz\Tests\DatabaseFactory('user');
            return $user_factory->create();
        },
        'topic_id' => function () {
            $topic_factory = new \Minz\Tests\DatabaseFactory('topic');
            return $topic_factory->create();
        },
    ]
);
