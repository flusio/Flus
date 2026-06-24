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

        if ($current_user->isAlphaEnabled()) {
            $new_stream_action = new ItemAction(
                label: TwigExtension::translate('New stream'),
                url: \Minz\Url::for('new stream'),
                icon: 'plus',
            );

            $stream_items = [];

            foreach ($current_user->streams() as $stream) {
                $stream_items[] = new Item(
                    label: $stream->name,
                    key: $stream->id,
                    url: \Minz\Url::for('stream', ['id' => $stream->id]),
                );
            }

            if (count($stream_items) === 0) {
                $stream_items[] = new ItemPlaceholder(
                    TwigExtension::translate('Create a stream to get started.'),
                );
            }

            $elements[] = new ItemGroup(
                label: TwigExtension::translate('Streams'),
                items: $stream_items,
                action: $new_stream_action,
            );
        }

        return $elements;
    }
}
