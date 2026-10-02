<?php

namespace App\models;

use App\utils;
use Minz\Database;
use Minz\ParameterBag;
use Minz\Translatable;
use Minz\Validable;

/**
 * A named set of form parameters, that the user can apply again later.
 *
 * A saved view is essentially a named query string: the forms that support
 * views pass their whole state through the URL, so restoring a view is just a
 * matter of visiting the right URL.
 *
 * @phpstan-import-type Parameters from ParameterBag
 * @phpstan-type ViewParameters array<string, string|list<string>>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'views')]
class View
{
    use Database\Recordable;
    use Database\Resource;
    use Validable;
    use utils\Memoizer;

    public const NAME_MAX_LENGTH = 50;

    /**
     * The parameters supported by the streams filters, in their stored form,
     * with their default values.
     */
    public const STREAM_PARAMETERS = [
        'at_offset' => '0',
        'days' => '1',
        'sources' => [],
        'status' => 'all',
        'with_dismissed' => '',
        'q' => '',
    ];

    /**
     * The reading statuses supported by the streams filters.
     */
    public const STREAM_STATUSES = ['all', 'unread', 'read', 'read-later'];

    /**
     * The number of days displayed by the streams filters.
     */
    public const STREAM_PERIOD_DAYS = 30;

    #[Database\Column]
    public string $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The name is required.'),
    )]
    #[Validable\Length(
        message: new Translatable('The name must be less than {max} characters.'),
        max: self::NAME_MAX_LENGTH,
    )]
    public string $name = '';

    /**
     * The saved parameters, in their stored form (e.g. `at_offset`).
     *
     * @var ViewParameters
     */
    #[Database\Column]
    public array $parameters = [];

    /**
     * The current parameters in their URL form. They come from the URL, or
     * from the saved parameters when the URL carries no filter.
     *
     * @var ViewParameters
     */
    public array $current_url_parameters = [];

    #[Database\Column]
    public bool $is_default = false;

    #[Database\Column]
    #[Validable\Presence(
        message: new Translatable('The user is required.'),
    )]
    public ?string $user_id = null;

    #[Database\Column]
    public ?string $stream_id = null;

    public function __construct(?User $user)
    {
        $this->id = \Minz\Random::timebased();

        if ($user) {
            $this->setUser($user);
        }
    }

    public function user(): ?User
    {
        return $this->memoize('user', function (): ?User {
            if ($this->user_id === null) {
                return null;
            }

            return User::require($this->user_id);
        });
    }

    public function setUser(User $user): void
    {
        $this->user_id = $user->id;
        $this->memoizeValue('user', $user);
    }

    /**
     * Return the id to use in the routes pointing to the view.
     *
     * An unsaved default view has no id in database: the "default" value
     * tells the views controller (cf. streams\Views::requireView()) to create
     * the row instead of updating it.
     */
    public function routeId(): string
    {
        return $this->isPersisted() ? $this->id : 'default';
    }

    public function stream(): ?Stream
    {
        return $this->memoize('stream', function (): ?Stream {
            if ($this->stream_id === null) {
                return null;
            }

            return Stream::require($this->stream_id);
        });
    }

    public function setStream(Stream $stream): void
    {
        $this->stream_id = $stream->id;
        $this->memoizeValue('stream', $stream);
    }

    /**
     * Load the current parameters from the given ones.
     *
     * The rule is all-or-nothing: if the parameters carry at least one
     * supported parameter, they all come from it, the missing ones falling
     * back to the defaults. When the parameters carry none, the view applies
     * its saved parameters, or the default ones if it was never saved.
     *
     * The loaded parameters are normalized: the current parameters always
     * carry valid values.
     */
    public function loadUrlParameters(ParameterBag $url_parameters): void
    {
        $default_url_parameters = $this->defaultUrlParameters();
        $current_url_parameters = [];

        $has_supported_parameter = utils\ArrayHelper::any(
            $this->supportedUrlParameters(),
            function (string $name) use ($url_parameters): bool {
                return $url_parameters->has($name);
            },
        );

        if ($has_supported_parameter) {
            foreach ($default_url_parameters as $name => $default_value) {
                if (is_array($default_value)) {
                    $current_url_parameters[$name] = $url_parameters->getArray($name, $default_value);
                } else {
                    $current_url_parameters[$name] = $url_parameters->getString($name, $default_value);
                }
            }
        } elseif ($this->parameters) {
            $current_url_parameters = $this->toUrlParameters($this->parameters);
        } else {
            $current_url_parameters = $default_url_parameters;
        }

        $this->current_url_parameters = $this->normalizeUrlParameters($current_url_parameters);
    }

    /**
     * Normalize the given URL parameters against the rules of the view type:
     * out-of-range or invalid values fall back to acceptable ones.
     *
     * @param Parameters $url_parameters
     *
     * @return ViewParameters
     */
    private function normalizeUrlParameters(array $url_parameters): array
    {
        if ($this->stream_id !== null) {
            return $this->normalizeStreamUrlParameters($url_parameters);
        }

        throw new \DomainException('Parameters cannot be normalized (unsupported view type)');
    }

    /**
     * Return the default parameters of the view, in their stored form.
     *
     * @return ViewParameters
     */
    public function defaultParameters(): array
    {
        if ($this->stream_id !== null) {
            return self::STREAM_PARAMETERS;
        }

        throw new \DomainException('Default parameters cannot be determined (unsupported view type)');
    }

    /**
     * Return the default parameters of the view, in their URL form.
     *
     * @return ViewParameters
     */
    public function defaultUrlParameters(): array
    {
        return $this->toUrlParameters($this->defaultParameters());
    }

    /**
     * Return the names of the parameters supported by the view, in their
     * stored form.
     *
     * @return string[]
     */
    public function supportedParameters(): array
    {
        return array_keys($this->defaultParameters());
    }

    /**
     * Return the names of the parameters supported by the view, in their
     * URL form.
     *
     * @return string[]
     */
    public function supportedUrlParameters(): array
    {
        return array_keys($this->defaultUrlParameters());
    }

    /**
     * Get current parameters in their stored form.
     *
     * @return ViewParameters
     */
    public function currentParameters(): array
    {
        return $this->toStoredParameters($this->current_url_parameters);
    }

    /**
     * Convert parameters from their URL form to their stored form.
     *
     * The parameters whose stored form is suffixed by "_offset" are dates:
     * they are stored as a number of days relative to today, so that a view
     * keeps its meaning over time instead of pointing at a frozen date.
     *
     * @param ViewParameters $url_parameters
     *
     * @return ViewParameters
     */
    private function toStoredParameters(array $url_parameters): array
    {
        $today = \Minz\Time::relative('today midnight');
        $supported_parameters = $this->supportedParameters();

        $parameters = [];

        foreach ($url_parameters as $name => $value) {
            if (is_string($value) && in_array("{$name}_offset", $supported_parameters, true)) {
                $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);
                $offset = $date ? (int) $today->diff($date->setTime(0, 0))->format('%r%a') : 0;

                $name = "{$name}_offset";
                $value = (string) $offset;
            }

            $parameters[$name] = $value;
        }

        return $parameters;
    }

    /**
     * Convert parameters from their stored form to their URL form.
     *
     * The parameters suffixed by "_offset" are relative to the current day:
     * they are resolved into absolute dates (e.g. "at_offset" => "-3"
     * becomes "at" => the date of 3 days ago).
     *
     * @param ViewParameters $parameters
     *
     * @return ViewParameters
     */
    private function toUrlParameters(array $parameters): array
    {
        $today = \Minz\Time::relative('today midnight');

        $url_parameters = [];

        foreach ($parameters as $name => $value) {
            if (str_ends_with($name, '_offset')) {
                $name = substr($name, 0, -strlen('_offset'));
                $value = $today->modify(intval($value) . ' days')->format('Y-m-d');
            }

            $url_parameters[$name] = $value;
        }

        return $url_parameters;
    }

    /**
     * Return whether the current parameters differ from the saved ones, i.e.
     * whether there is something to save.
     *
     * The saved parameters are normalized before the comparison, as the
     * current ones are: a view saved with outdated parameters (e.g. a source
     * removed from the stream, or a parameter which no longer exists) is not
     * modified as long as it applies the same filters.
     */
    public function isModified(): bool
    {
        $saved_url_parameters = $this->toUrlParameters($this->parameters);
        $saved_url_parameters = $this->normalizeUrlParameters($saved_url_parameters);
        $saved_parameters = $this->toStoredParameters($saved_url_parameters);

        return $this->currentParameters() != $saved_parameters;
    }

    /**
     * Save the current parameters.
     */
    public function saveParameters(): void
    {
        $this->parameters = $this->currentParameters();
        $this->save();
    }

    /**
     * Return the default view of the given stream, or a new unsaved one with
     * default parameters.
     *
     * The user is the one who would own the view once saved: it can be null
     * (i.e. an anonymous visitor), and such a view cannot be saved.
     */
    public static function findOrBuildDefaultForStream(Stream $stream, ?User $user): self
    {
        $existing_view = self::findBy([
            'stream_id' => $stream->id,
            'is_default' => true,
        ]);

        if ($existing_view) {
            $existing_view->setStream($stream);
            return $existing_view;
        }

        $view = new self($user);
        $view->setStream($stream);
        $view->name = _('Main view');
        $view->parameters = $view->defaultParameters();
        $view->is_default = true;

        return $view;
    }

    /**
     * Return the views of the given stream, except the default one.
     *
     * @return self[]
     */
    public static function listByStream(Stream $stream): array
    {
        $views = self::listBy([
            'stream_id' => $stream->id,
            'is_default' => false,
        ]);

        return utils\Sorter::localeSort($views, 'name');
    }

    /**
     * Normalize the parameters of a view on the streams filters.
     *
     * @param Parameters $url_parameters
     *
     * @return ViewParameters
     */
    private function normalizeStreamUrlParameters(array $url_parameters): array
    {
        // Each supported parameter is set explicitly below: unknown parameters
        // are dropped and missing ones fall back to their default value.
        $parameters = new ParameterBag($url_parameters);
        $normalized = [];

        $today = \Minz\Time::relative('today midnight');
        $period_days = self::STREAM_PERIOD_DAYS - 1;
        $oldest = \Minz\Time::relative("-{$period_days} days midnight");
        $at = $parameters->getDatetime('at', $today, 'Y-m-d');
        $at = min(max($at, $oldest), $today);
        $normalized['at'] = $at->format('Y-m-d');

        $days = $parameters->getInteger('days', 1);
        $days = min(max($days, 1), 7);
        $normalized['days'] = (string) $days;

        // Only the sources of the stream are kept. They are sorted so that the
        // order of selection doesn't matter when comparing the parameters (cf.
        // isModified()).
        $stream_source_ids = [];
        $stream = $this->stream();
        if ($stream) {
            $stream_sources = $stream->sources(['context_user' => $stream->owner()]);
            $stream_source_ids = array_column($stream_sources, 'id');
        }

        $source_ids = $parameters->getArray('sources');
        $source_ids = array_filter($source_ids, 'is_string');
        $source_ids = array_intersect($source_ids, $stream_source_ids);
        $source_ids = array_unique($source_ids);
        sort($source_ids);

        $normalized['sources'] = $source_ids;

        $status = $parameters->getString('status', 'all');
        if (!in_array($status, self::STREAM_STATUSES)) {
            $status = 'all';
        }
        $normalized['status'] = $status;

        $normalized['with_dismissed'] = $parameters->getBoolean('with_dismissed') ? '1' : '';

        $normalized['q'] = trim($parameters->getString('q', ''));

        return $normalized;
    }

    #[Validable\Check]
    public function checkNameIsUnique(): void
    {
        // The views of a stream share a single namespace, whoever created
        // them: the name is what identifies a view in the bar.
        $existing_view = self::findBy([
            'stream_id' => $this->stream_id,
            'name' => $this->name,
        ]);

        if ($existing_view && $existing_view->id !== $this->id) {
            $this->addError(
                'name',
                'unique',
                _('A view with this name already exists, please choose another one.'),
            );
        }
    }
}
