<?php

$environment = \Minz\Configuration::$environment;

// Make sure to initiaze the support user
\flusio\models\User::supportUser();

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
    $reset_demo_job = new \flusio\jobs\ResetDemo();
    $job_dao = new \flusio\models\dao\Job();
    if (!$job_dao->findBy(['name' => $reset_demo_job->name])) {
        $reset_demo_job->perform_at = \Minz\Time::relative('tomorrow 2:00');
        $reset_demo_job->frequency = '+1 day';
        $reset_demo_job->performLater();
    }
}

$subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];
if ($subscriptions_enabled) {
    $subscriptions_sync_job = new \flusio\jobs\SubscriptionsSync();
    $job_dao = new \flusio\models\dao\Job();
    if (!$job_dao->findBy(['name' => $subscriptions_sync_job->name])) {
        $subscriptions_sync_job->frequency = '+4 hours';
        $subscriptions_sync_job->performLater();
    }
}
