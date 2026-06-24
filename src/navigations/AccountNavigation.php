<?php

namespace App\navigations;

use App\auth;
use Minz\Template\TwigExtension;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class AccountNavigation extends BaseNavigation
{
    public function title(): string
    {
        return TwigExtension::translate('Account & data menu');
    }

    public function elements(): array
    {
        $current_user = auth\CurrentUser::require();

        $account_items = [];
        $data_items = [];
        $security_items = [];

        $account_items[] = new Item(
            label: TwigExtension::translate('Overview'),
            key: 'account',
            url: \Minz\Url::for('account'),
            icon: 'cog',
        );

        if (!$current_user->isValidated()) {
            $account_items[] = new Item(
                label: TwigExtension::translate('Account validation'),
                key: 'account validation',
                url: \Minz\Url::for('account validation'),
                icon: 'check',
            );
        }

        if (!$current_user->isBlocked()) {
            $account_items[] = new Item(
                label: TwigExtension::translate('Mastodon'),
                key: 'mastodon',
                url: \Minz\Url::for('mastodon'),
                icon: 'mastodon',
            );
        }

        if (!$current_user->isBlocked()) {
            $data_items[] = new Item(
                label: TwigExtension::translate('OPML import'),
                key: 'opml',
                url: \Minz\Url::for('opml'),
                icon: 'upload',
            );
        }

        $data_items[] = new Item(
            label: TwigExtension::translate('Data export'),
            key: 'exportation',
            url: \Minz\Url::for('exportation'),
            icon: 'cloud-download',
        );

        $security_items[] = new Item(
            label: TwigExtension::translate('Credentials'),
            key: 'security',
            url: \Minz\Url::for('security'),
            icon: 'key',
        );

        $security_items[] = new Item(
            label: TwigExtension::translate('Sessions'),
            key: 'sessions',
            url: \Minz\Url::for('sessions'),
            icon: 'session',
        );

        $security_items[] = new Item(
            label: TwigExtension::translate('Account deletion'),
            key: 'account deletion',
            url: \Minz\Url::for('account deletion'),
            icon: 'trash',
        );

        return [
            new ItemGroup(TwigExtension::translate('Account'), $account_items),
            new ItemGroup(TwigExtension::translate('Data'), $data_items),
            new ItemGroup(TwigExtension::translate('Security'), $security_items),
        ];
    }
}
