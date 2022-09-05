<?php

$environment = \Minz\Configuration::$environment;
$application = new \flusio\cli\Application();

// Make sure that the technical user is initialized
\flusio\models\User::supportUser();

// Install the scheduled jobs in database
$application->run(new \Minz\Request('cli', '/jobs/install'));

if ($environment === 'development') {
    // Initialize topics (only in development)
    \flusio\models\Topic::findOrCreateBy(['label' => _('Business')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Climate')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Culture')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Health')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Politics')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Science')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Sport')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Tech')]);
}
