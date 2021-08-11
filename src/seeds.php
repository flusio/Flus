<?php

$environment = \Minz\Configuration::$environment;

// Make sure to initiaze the support user
\flusio\models\User::supportUser();

$job_dao = new \flusio\models\dao\Job();

$feeds_sync_job = new \flusio\jobs\scheduled\FeedsSync();
$feeds_sync_job->install();

$links_fetcher_job = new \flusio\jobs\scheduled\LinksFetcher();
$links_fetcher_job->install();

$cleaner_job = new \flusio\jobs\scheduled\Cleaner();
if (!$job_dao->findBy(['name' => $cleaner_job->name])) {
    $cleaner_job->performLater();
}

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

$subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];
if ($subscriptions_enabled) {
    $subscriptions_sync_job = new \flusio\jobs\scheduled\SubscriptionsSync();
    if (!$job_dao->findBy(['name' => $subscriptions_sync_job->name])) {
        $subscriptions_sync_job->performLater();
    }
}
