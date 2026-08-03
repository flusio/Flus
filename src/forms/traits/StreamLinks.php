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

    #[Form\Field]
    public string $source = '';

    #[Form\Field]
    public string $status = 'all';

    #[Form\Field(transform: 'trim')]
    public string $q = '';

    #[Form\Field(format: 'Y-m-d H:i:sP')]
    public ?\DateTimeImmutable $before = null;

    /**
     * @return models\Link[]
     */
    public function links(): array
    {
        $user = $this->optionAs('user', models\User::class);
        $stream = $this->optionAs('stream', models\Stream::class);

        $source = null;
        if ($this->source) {
            $source = models\Collection::find($this->source);
            if (!$source) {
                // The source no longer exists: better mark no link at all
                // than marking the links of the other sources.
                return [];
            }
        }

        $status = $this->status;
        if (!in_array($status, ['all', 'unread', 'read', 'read-later'])) {
            $status = 'all';
        }

        $search_query = null;
        if ($this->q !== '') {
            try {
                $search_query = search_engine\Query::fromString($this->q);
            } catch (\LogicException) {
                // The query is malformed: it is ignored, exactly as the
                // StreamView does when rendering the timeline.
            }
        }

        $stream_links = $stream->links([
            'context_user' => $user,
            'at' => $this->at ?? \Minz\Time::now(),
            'days' => $this->days,
            'source' => $source,
            'status' => $status,
            'query' => $search_query,
            'created_before' => $this->before ?? \Minz\Time::now(),
        ]);

        // Deduplicate the links by url_hash: a stream can list the same URL
        // several times (e.g. two sources publishing the same link), while
        // obtainLinks() would create duplicated user links (the index on
        // (user_id, url_hash) is not unique).
        $stream_links = array_values(array_column($stream_links, null, 'url_hash'));

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
                    $link->source_id = $source_id;
                    $link->setOrigin(\Minz\Url::absoluteFor('collection', ['id' => $source_id]));
                }

                $links_to_create[] = $link;
            }
        }

        models\Link::bulkInsert($links_to_create);

        return $links;
    }
}
