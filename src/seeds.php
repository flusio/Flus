<?php

$environment = \Minz\Configuration::$environment;

// Make sure to initiaze the support user
\flusio\models\User::supportUser();

$job_dao = new \flusio\models\dao\Job();

$feeds_sync_job = new \flusio\jobs\scheduled\FeedsSync();
if (!$job_dao->findBy(['name' => $feeds_sync_job->name])) {
    $feeds_sync_job->performLater();
}

$links_fetcher_job = new \flusio\jobs\scheduled\LinksFetcher();
if (!$job_dao->findBy(['name' => $links_fetcher_job->name])) {
    $links_fetcher_job->performLater();
}

$cache_cleaner_job = new \flusio\jobs\scheduled\CacheCleaner();
if (!$job_dao->findBy(['name' => $cache_cleaner_job->name])) {
    $cache_cleaner_job->performLater();
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

$demo = \Minz\Configuration::$application['demo'];
if ($demo) {
    $reset_demo_job = new \flusio\jobs\scheduled\ResetDemo();
    if (!$job_dao->findBy(['name' => $reset_demo_job->name])) {
        $reset_demo_job->performLater();
    }
}

$subscriptions_enabled = \Minz\Configuration::$application['subscriptions_enabled'];
if ($subscriptions_enabled) {
    $subscriptions_sync_job = new \flusio\jobs\scheduled\SubscriptionsSync();
    if (!$job_dao->findBy(['name' => $subscriptions_sync_job->name])) {
        $subscriptions_sync_job->performLater();
    }
}
