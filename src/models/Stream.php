<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'streams')]
class Stream
{
    use Database\Recordable;
    use Database\Resource;
    use Validable;
    use utils\Memoizer;

    public const NAME_MAX_LENGTH = 100;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The name is required.'),
    )]
    #[Validable\Length(
        max: self::NAME_MAX_LENGTH,
        message: new Translatable('The name must be less than {max} characters.'),
    )]
    public string $name = '';

    #[Database\Column]
    public string $description = '';

    #[Database\Column]
    public bool $is_public = false;

    #[Database\Column]
    public ?string $image_filename = null;

    #[Database\Column]
    public string $user_id;

    public function __construct(User $user)
    {
        $this->id = \Minz\Random::timebased();
        $this->setOwner($user);
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * Return the description as HTML (from Markdown).
     */
    public function descriptionAsHtml(): string
    {
        $markdown = new utils\MiniMarkdown(context_user: $this->owner());
        return $markdown->text($this->description());
    }

    public function url(): string
    {
        return \Minz\Url::absoluteFor('stream', ['id' => $this->id]);
    }

    public function owner(): User
    {
        return $this->memoize('owner', function (): User {
            return User::require($this->user_id);
        });
    }

    public function setOwner(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('owner', $user);
    }

    /**
     * @return Collection[]
     */
    public function sources(): array
    {
        return $this->memoize('sources', function (): array {
            $collections = Collection::listByStream($this);
            return utils\Sorter::localeSort($collections, 'name');
        });
    }

    public function hasSource(Collection $source): bool
    {
        return StreamToFollow::find($this, $source) !== null;
    }

    public function addSource(Collection $source): void
    {
        StreamToFollow::findOrCreate($this, $source);
        $this->unmemoize('sources');
    }

    public function removeSource(Collection $source): void
    {
        $stream_to_follow = StreamToFollow::find($this, $source);
        if ($stream_to_follow) {
            $stream_to_follow->remove();
        }
        $this->unmemoize('sources');
    }

    public function publicationFrequencyPerYear(): int
    {
        $sources = $this->sources();
        return array_sum(array_column($sources, 'publication_frequency_per_year'));
    }

    /**
     * @param array{
     *     context_user?: ?User,
     *     at?: \DateTimeImmutable,
     *     days?: int,
     * } $options
     *
     * @return Link[]
     */
    public function links(array $options = []): array
    {
        return Link::listByStream($this, $options);
    }

    /**
     * Return a tag URI that can be used as Atom id
     *
     * @see https://www.rfc-editor.org/rfc/rfc4151.txt
     */
    public function tagUri(): string
    {
        $host = \App\Configuration::$url_options['host'];
        $date = $this->created_at->format('Y-m-d');
        return "tag:{$host},{$date}:streams/{$this->id}";
    }
}
