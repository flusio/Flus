<?php

$app_path = realpath(__DIR__ . '/..');

assert($app_path !== false);

include $app_path . '/autoload.php';

\Minz\Configuration::load('test', $app_path);

\Minz\Engine::startSession();

\Minz\Database::reset();
$schema = @file_get_contents(\Minz\Configuration::$schema_path);

assert($schema !== false);

$database = \Minz\Database::get();
$database->exec($schema);

$faker = \Faker\Factory::create();

$faker_seed = getenv('SEED');
if ($faker_seed) {
    $faker_seed = intval($faker_seed);
} else {
    $faker_seed = random_int(PHP_INT_MIN, PHP_INT_MAX);
}

$faker->seed($faker_seed);
echo 'Use SEED=' . $faker_seed . " to reproduce this suite.\n";
