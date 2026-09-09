<?php

namespace App\navigations;

use App\auth;
use App\models;
use App\twig;
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
                key: 'read later',
                url: \Minz\Url::for('read later'),
                icon: 'bookmark',
            ),

            new Item(
                label: TwigExtension::translate('Links read'),
                key: 'read',
                url: \Minz\Url::for('read list'),
                icon: 'check',
            ),

            new Item(
                label: TwigExtension::translate('Sources'),
                key: 'sources',
                url: \Minz\Url::for('sources'),
                icon: 'feed',
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

        $streams = $current_user->streams();
        $shared_streams = $current_user->sharedStreams();

        // Preload the unread dots.
        $streams_with_unread_dot = array_filter(
            array_merge($streams, $shared_streams),
            function (models\Stream $stream): bool {
                return $stream->display_unread_in_sidenav;
            },
        );

        models\streams\Preloader::for($streams_with_unread_dot)->hasUnreadLinksFor($current_user);

        $new_stream_action = new ItemAction(
            label: TwigExtension::translate('New stream'),
            url: \Minz\Url::for('new stream'),
            icon: 'plus',
        );

        $stream_items = [];

        foreach ($streams as $stream) {
            $stream_items[] = $this->streamItem($stream, $current_user);
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

        if ($shared_streams) {
            $shared_stream_items = [];

            foreach ($shared_streams as $stream) {
                $shared_stream_items[] = $this->streamItem($stream, $current_user);
            }

            $elements[] = new ItemGroup(
                label: TwigExtension::translate('Shared streams'),
                items: $shared_stream_items,
            );
        }

        return $elements;
    }

    private function streamItem(models\Stream $stream, models\User $current_user): Item
    {
        $unread_links_label = TwigExtension::translate('Has unread links from the last seven days');

        return new Item(
            label: $stream->name,
            key: $stream->id,
            url: \Minz\Url::for('stream', ['id' => $stream->id]),
            image_filename: twig\UrlExtension::urlMedia('covers', $stream->image_filename, 'stream-card.png'),
            dot_label: $stream->displaysUnreadInSidenav($current_user) ? $unread_links_label : '',
        );
    }
}
