<?php

namespace App\navigations;

use App\auth\CurrentUser;
use Minz\Template\TwigExtension;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AccountNavigation extends BaseNavigation
{
    public function elements(): array
    {
        $current_user = CurrentUser::require();

        $account_items = [];
        $data_items = [];
        $security_items = [];

        $account_items[] = new Item(
            'account',
            \Minz\Url::for('account'),
            'cog',
            TwigExtension::translate('Overview'),
        );

        if (!$current_user->isValidated()) {
            $account_items[] = new Item(
                'account validation',
                \Minz\Url::for('account validation'),
                'check',
                TwigExtension::translate('Account validation'),
            );
        }

        if (!$current_user->isBlocked()) {
            $account_items[] = new Item(
                'mastodon',
                \Minz\Url::for('mastodon'),
                'mastodon',
                TwigExtension::translate('Mastodon'),
            );
        }

        if (!$current_user->isBlocked()) {
            $data_items[] = new Item(
                'opml',
                \Minz\Url::for('opml'),
                'upload',
                TwigExtension::translate('OPML import'),
            );
        }

        $data_items[] = new Item(
            'exportation',
            \Minz\Url::for('exportation'),
            'backup',
            TwigExtension::translate('Data export'),
        );

        $security_items[] = new Item(
            'security',
            \Minz\Url::for('security'),
            'key',
            TwigExtension::translate('Credentials'),
        );

        $security_items[] = new Item(
            'sessions',
            \Minz\Url::for('sessions'),
            'connect',
            TwigExtension::translate('Sessions'),
        );

        $security_items[] = new Item(
            'account deletion',
            \Minz\Url::for('account deletion'),
            'trash',
            TwigExtension::translate('Account deletion'),
        );

        return [
            new ItemGroup(TwigExtension::translate('Account'), $account_items),
            new ItemGroup(TwigExtension::translate('Data'), $data_items),
            new ItemGroup(TwigExtension::translate('Security'), $security_items),
        ];
    }
}
