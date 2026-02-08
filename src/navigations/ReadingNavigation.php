<?php

namespace App\navigations;

use Minz\Template\TwigExtension;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ReadingNavigation extends BaseNavigation
{
    public function elements(): array
    {
        return [
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
                TwigExtension::translate('Bookmarks'),
            ),

            new Item(
                'read',
                \Minz\Url::for('read list'),
                'check',
                TwigExtension::translate('Links read'),
            ),

            new Item(
                'explore',
                \Minz\Url::for('explore'),
                'compass',
                TwigExtension::translate('Explore'),
            ),
        ];
    }
}
