<?php

namespace App\migrations;

use App\models;

class Migration202511290002SyncLinksFetchedRetryAfter
{
    public function migrate(): bool
    {
        $links = models\Link::listBy([
            'to_be_fetched' => true,
        ]);

        foreach ($links as $link) {
            $link->fetched_retry_at = $link->fetchAgainAfter();
            $link->save();
        }

        return true;
    }

    public function rollback(): bool
    {
        return true;
    }
}
