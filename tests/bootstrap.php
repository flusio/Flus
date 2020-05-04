<?php

$app_path = realpath(__DIR__ . '/..');

include $app_path . '/autoload.php';

\Minz\Configuration::load('test', $app_path);
\Minz\Environment::initialize();
\Minz\Environment::startSession();
