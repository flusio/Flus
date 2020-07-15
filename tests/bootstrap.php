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
            return bin2hex(random_bytes(16));
        },
        'created_at' => function () use ($faker) {
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
        'locale' => function () use ($faker) {
            $available_locales = \flusio\utils\Locale::availableLocales();
            return $faker->randomElement($available_locales);
        },
        'csrf' => function () {
            return \bin2hex(\random_bytes(32));
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
            return bin2hex(random_bytes(8));
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
            return bin2hex(random_bytes(16));
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
            return bin2hex(random_bytes(16));
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
            return bin2hex(random_bytes(16));
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
