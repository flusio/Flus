<?php

namespace App\navigations;

use App\auth;
use Minz\Template\TwigExtension;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ReadingNavigation extends BaseNavigation
{
    public function title(): string
    {
        return TwigExtension::translate('Reading menu');
    }

    public function elements(): array
    {
        $current_user = auth\CurrentUser::require();

        $elements = [
            new Item(
                label: TwigExtension::translate('News'),
                key: 'news',
                url: \Minz\Url::for('news'),
                icon: 'news',
            ),

            new Item(
                label: TwigExtension::translate('To read'),
                key: 'bookmarks',
                url: \Minz\Url::for('bookmarks'),
                icon: 'bookmark',
            ),

            new Item(
                label: TwigExtension::translate('Links read'),
                key: 'read',
                url: \Minz\Url::for('read list'),
                icon: 'check',
            ),
        ];

        if ($current_user->isBetaEnabled()) {
            $elements[] = new Item(
                label: TwigExtension::translate('Explore'),
                key: 'explore',
                url: \Minz\Url::for('explore'),
                icon: 'compass',
            );
        }

        return $elements;
    }
}
