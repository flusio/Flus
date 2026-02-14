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
    public function elements(): array
    {
        $current_user = auth\CurrentUser::require();

        $elements = [
            new Item(
                'news',
                \Minz\Url::for('news'),
                'reading',
                TwigExtension::translate('News'),
            ),

            new Item(
                'bookmarks',
                \Minz\Url::for('bookmarks'),
                'bookmark',
                TwigExtension::translate('To read'),
            ),

            new Item(
                'read',
                \Minz\Url::for('read list'),
                'check',
                TwigExtension::translate('Links read'),
            ),
        ];

        if ($current_user->isBetaEnabled()) {
            $elements[] = new Item(
                'explore',
                \Minz\Url::for('explore'),
                'compass',
                TwigExtension::translate('Explore'),
            );
        }

        return $elements;
    }
}
