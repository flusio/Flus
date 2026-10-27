<?php

namespace App\services;

use App\auth;
use App\models;
use App\utils;
use Minz\Template\TwigExtension;

/**
 * Service to generate OPML files.
 *
 * @phpstan-type Options array{
 *     context_user?: ?models\User,
 *     with_time_filter?: bool,
 * }
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OpmlGenerator
{
    /**
     * Return the OPML representation of the sources followed by the given
     * user, grouped by streams.
     *
     * The sources outside of the streams are private: they are only rendered
     * if the context user is the given user.
     *
     * @param Options $options
     */
    public function renderFollows(models\User $user, array $options = []): string
    {
        $context_user = $options['context_user'] ?? null;
        $with_time_filter = $options['with_time_filter'] ?? false;

        $is_owner = $context_user && $context_user->id === $user->id;

        $streams = $user->streams();
        if (!$is_owner) {
            $streams = array_filter(
                $streams,
                function (models\Stream $stream) use ($context_user): bool {
                    return auth\Access::can($context_user, 'view', $stream);
                },
            );
        }

        $sources_by_streams = $this->listSourcesByStreams($streams, $context_user);

        $sources_in_stream = [];
        foreach ($sources_by_streams as $stream_sources) {
            foreach ($stream_sources as $source) {
                $sources_in_stream[$source->id] = true;
            }
        }

        $no_stream_sources = [];
        if ($is_owner) {
            $no_stream_sources = array_filter(
                $user->followedSources(),
                function (models\Collection $source) use ($sources_in_stream): bool {
                    return !isset($sources_in_stream[$source->id]);
                },
            );
        }

        return $this->render([
            'title' => TwigExtension::translate('%s’s sources', [$user->username]),
            'sources' => $no_stream_sources,
            'streams' => $streams,
            'sources_by_streams' => $sources_by_streams,
            'context_user' => $context_user,
            'with_time_filter' => $with_time_filter,
        ]);
    }

    /**
     * Return the OPML representation of the collections of the given user.
     *
     * @param Options $options
     */
    public function renderCollections(models\User $user, array $options = []): string
    {
        $context_user = $options['context_user'] ?? null;
        $with_time_filter = $options['with_time_filter'] ?? false;

        $is_owner = $context_user && $context_user->id === $user->id;

        $collections = $user->collections();
        if (!$is_owner) {
            $collections = array_filter(
                $collections,
                function (models\Collection $collection) use ($context_user): bool {
                    return auth\Access::can($context_user, 'view', $collection);
                },
            );
        }

        $collections = utils\Sorter::localeSort($collections, 'name');

        return $this->render([
            'title' => TwigExtension::translate('%s’s collections', [$user->username]),
            'sources' => $collections,
            'streams' => [],
            'sources_by_streams' => [],
            'context_user' => $context_user,
            'with_time_filter' => $with_time_filter,
        ]);
    }

    /**
     * Return the OPML representation of the sources of the given stream.
     *
     * @param Options $options
     */
    public function renderStream(models\Stream $stream, array $options = []): string
    {
        $context_user = $options['context_user'] ?? null;
        $with_time_filter = $options['with_time_filter'] ?? false;

        $sources_by_streams = $this->listSourcesByStreams([$stream], $context_user);

        return $this->render([
            'title' => $stream->name,
            'sources' => [],
            'streams' => [$stream],
            'sources_by_streams' => $sources_by_streams,
            'context_user' => $context_user,
            'with_time_filter' => $with_time_filter,
        ]);
    }

    /**
     * Return the sources of the given streams that the context user can view,
     * indexed by stream id and sorted by the names given by the context user.
     *
     * @param models\Stream[] $streams
     *
     * @return array<string, models\Collection[]>
     */
    private function listSourcesByStreams(array $streams, ?models\User $context_user): array
    {
        $sources_by_streams = models\Collection::listByStreams($streams, [
            'context_user' => $context_user,
        ]);

        return array_map(
            function (array $sources) use ($context_user): array {
                return utils\Sorter::localeSort(
                    $sources,
                    function (models\Collection $source) use ($context_user): string {
                        return $source->nameByUser($context_user);
                    },
                );
            },
            $sources_by_streams,
        );
    }

    /**
     * Return the OPML rendered from the given context, formatted.
     *
     * @param array{
     *     title: string,
     *     sources: models\Collection[],
     *     streams: models\Stream[],
     *     sources_by_streams: array<string, models\Collection[]>,
     *     context_user: ?models\User,
     *     with_time_filter: bool,
     * } $context
     */
    private function render(array $context): string
    {
        $view = new \Minz\Template\Twig('opml/index.xml.twig', $context);
        return utils\Xml::format($view->render());
    }
}
