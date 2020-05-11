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
    'users',
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
    ]
);

\Minz\Tests\DatabaseFactory::addFactory(
    'tokens',
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
        'type' => function () use ($faker) {
            return $faker->randomElement(\flusio\models\Token::VALID_TYPES);
        },
        'user_id' => function () {
            $users_factory = new \Minz\Tests\DatabaseFactory('users');
            return $users_factory->create();
        },
    ]
);
