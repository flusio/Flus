<?php

namespace App\migrations;

use App\models;

class Migration202512220001MigrateMastodonAccountOptions
{
    public function migrate(): bool
    {
        $mastodon_accounts = models\MastodonAccount::listAll();

        foreach ($mastodon_accounts as $mastodon_account) {
            $options = $mastodon_account->options;

            $mastodon_account->options = [
                'prefill_with_notes' => true,
                // @phpstan-ignore offsetAccess.notFound
                'link_to_notes' => $options['link_to_comment'] === 'always' || $options['link_to_comment'] === 'auto',
                'post_scriptum' => $options['post_scriptum'],
                'post_scriptum_in_all_posts' => false,
            ];
            $mastodon_account->save();
        }

        return true;
    }

    public function rollback(): bool
    {
        $mastodon_accounts = models\MastodonAccount::listAll();

        foreach ($mastodon_accounts as $mastodon_account) {
            $options = $mastodon_account->options;

            // @phpstan-ignore assign.propertyType
            $mastodon_account->options = [
                'link_to_comment' => $options['link_to_notes'] ? 'auto' : 'never',
                'post_scriptum' => $options['post_scriptum'],
            ];
            $mastodon_account->save();
        }

        return true;
    }
}
