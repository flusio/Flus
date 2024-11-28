<?php

$environment = \App\Configuration::$environment;
$application = new \App\cli\Application();

// Make sure that the technical user is initialized
\App\models\User::supportUser();

// Install the scheduled jobs in database
$application->run(new \Minz\Request('CLI', '/jobs/install'));

if ($environment === 'development') {
    // Initialize topics (only in development)
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Business')],
        ['id' => \Minz\Random::timebased()],
    );
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Climate')],
        ['id' => \Minz\Random::timebased()],
    );
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Culture')],
        ['id' => \Minz\Random::timebased()],
    );
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Health')],
        ['id' => \Minz\Random::timebased()],
    );
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Politics')],
        ['id' => \Minz\Random::timebased()],
    );
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Science')],
        ['id' => \Minz\Random::timebased()],
    );
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Sport')],
        ['id' => \Minz\Random::timebased()],
    );
    \App\models\Topic::findOrCreateBy(
        ['label' => _('Tech')],
        ['id' => \Minz\Random::timebased()],
    );
}
