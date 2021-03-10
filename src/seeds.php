<?php

$environment = \Minz\Configuration::$environment;

if ($environment === 'development') {
    \flusio\models\Topic::findOrCreateBy(['label' => _('Business')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Climate')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Culture')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Health')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Politics')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Science')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Sport')]);
    \flusio\models\Topic::findOrCreateBy(['label' => _('Tech')]);
}

$demo = \Minz\Configuration::$application['demo'];
if ($demo) {
    $user = \flusio\models\User::findOrCreateBy([
        'username' => 'Abby',
        'email' => 'demo@flus.io',
    ], [
        'password_hash' => password_hash('demo', PASSWORD_BCRYPT),
        'validated_at' => \Minz\Time::now(),
        'locale' => \flusio\utils\Locale::currentLocale(),
    ]);

    \flusio\models\Collection::findOrCreateBy([
        'type' => 'bookmarks',
        'user_id' => $user->id,
    ], [
        'name' => _('Bookmarks'),
    ]);
}
