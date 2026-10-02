<?php

namespace App\forms\traits;

use App\models;
use App\search_engine;
use Minz\Form;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait StreamLinks
{
    #[Form\Field(format: 'Y-m-d')]
    public ?\DateTimeImmutable $at = null;

    #[Form\Field]
    public int $days = 1;

    /** @var string[] */
    #[Form\Field]
    public array $sources = [];

    #[Form\Field]
    public string $status = 'all';

    #[Form\Field]
    public bool $with_dismissed = false;

    #[Form\Field(transform: 'trim')]
    public string $q = '';

    #[Form\Field(format: 'Y-m-d H:i:sP')]
    public ?\DateTimeImmutable $before = null;

    /**
     * @return models\Link[]
     */
    public function links(bool $obtain_links = true): array
    {
        $user = $this->optionAs('user', models\User::class);
        $stream = $this->optionAs('stream', models\Stream::class);

        $sources = [];
        if ($this->sources) {
            $sources = $stream->sources(['context_user' => $user]);
            $sources = array_filter($sources, function (models\Collection $source): bool {
                return in_array($source->id, $this->sources);
            });
            $sources = array_values($sources);

            if (!$sources) {
                // The sources no longer exist or cannot be viewed. Better mark
                // no link at all than marking the links of all the sources.
                return [];
            }
        }

        $status = $this->status;
        if (!in_array($status, models\View::STREAM_STATUSES)) {
            $status = 'all';
        }

        $search_query = null;
        if ($this->q !== '') {
            try {
                $search_query = search_engine\LinksSearcher::buildQuery($this->q, context: 'stream');
            } catch (search_engine\SyntaxError) {
                // The query cannot be parsed: no links are displayed to the
                // user in this case (see StreamView), so none must be marked.
                return [];
            }
        }

        $stream_links = $stream->links([
            'context_user' => $user,
            'at' => $this->at ?? \Minz\Time::now(),
            'days' => $this->days,
            'sources' => $sources,
            'status' => $status,
            'with_dismissed' => $this->with_dismissed,
            'query' => $search_query,
            'created_before' => $this->before ?? \Minz\Time::now(),
        ]);

        // Deduplicate the links by url_hash: a stream can list the same URL
        // several times (e.g. two sources publishing the same link), while
        // obtainLinks() would create duplicated user links (the index on
        // (user_id, url_hash) is not unique).
        $stream_links = array_values(array_column($stream_links, null, 'url_hash'));

        if (!$obtain_links) {
            return $stream_links;
        }

        $source_ids_by_url_hash = [];
        foreach ($stream_links as $stream_link) {
            if ($stream_link->source_id) {
                $source_ids_by_url_hash[$stream_link->url_hash] = $stream_link->source_id;
            }
        }

        $links = $user->obtainLinks($stream_links);

        $links_to_create = [];

        foreach ($links as $link) {
            if (!$link->isPersisted()) {
                $link->created_at = \Minz\Time::now();

                $source_id = $source_ids_by_url_hash[$link->url_hash] ?? null;
                if ($source_id) {
                    $link->setSourceId($source_id);
                    $link->setOrigin(\Minz\Url::absoluteFor('collection', ['id' => $source_id]));
                }

                $links_to_create[] = $link;
            }
        }

        models\Link::bulkInsert($links_to_create);

        return $links;
    }
}
