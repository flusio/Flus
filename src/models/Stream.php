<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

#[Database\Table(name: 'streams')]
class Stream
{
    use Database\Recordable;
    use Database\Resource;
    use utils\Memoizer;
    use Validable;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    public string $name = '';

    #[Database\Column]
    public string $description = '';

    #[Database\Column]
    public string $image_filename = '';

    #[Database\Column]
    public string $user_id;

    public function __construct()
    {
        $this->id = \Minz\Random::timebased();
    }

    public function user(): User
    {
        return $this->memoize('user', function (): User {
            return User::requireBy([
                'id' => $this->user_id,
            ]);
        });
    }

    public function descriptionAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown(context_user: $this->user());
        return $markdown->text($this->description);
    }

    public function sources(): array
    {
        return $this->memoize('sources', function (): array {
            return Collection::listByStreamId($this->id);
        });
    }

    public function addSource(Collection $source): void
    {
        $stream_to_collection = new StreamToCollection($this, $source);
        $stream_to_collection->save();
    }

    public function removeSource(Collection $source): void
    {
        $stream_to_collection = StreamToCollection::findBy([
            'stream_id' => $this->id,
            'collection_id' => $source->id,
        ]);
        if ($stream_to_collection) {
            $stream_to_collection->remove();
        }
    }

    public function publicationFrequencyPerYear(): int
    {
        $sources = $this->sources();
        return array_sum(array_column($sources, 'publication_frequency_per_year'));
    }

    public function links(array $options = []): array
    {
        return Link::listByStreamId($this->id, $options);
    }

    public function countLinks(array $options = []): int
    {
        return Link::countByStreamId($this->id, $options);
    }
}
