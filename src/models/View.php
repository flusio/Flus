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
 * @phpstan-type ViewParameters array<string, string>
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
        'source' => '',
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
                $current_url_parameters[$name] = $url_parameters->getString($name, $default_value);
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
     * @param ViewParameters $url_parameters
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
     * Get current parameters from their URL form to their stored form.
     *
     * The parameters whose stored form is suffixed by "_offset" are dates:
     * they are stored as a number of days relative to today, so that a view
     * keeps its meaning over time instead of pointing at a frozen date.
     *
     * @return ViewParameters
     */
    public function currentParameters(): array
    {
        $today = \Minz\Time::relative('today midnight');
        $supported_parameters = $this->supportedParameters();

        $parameters = [];

        foreach ($this->current_url_parameters as $name => $value) {
            if (in_array("{$name}_offset", $supported_parameters, true)) {
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
     */
    public function isModified(): bool
    {
        return $this->currentParameters() != $this->parameters;
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
     * @param ViewParameters $url_parameters
     *
     * @return ViewParameters
     */
    private function normalizeStreamUrlParameters(array $url_parameters): array
    {
        // Starting from the defaults guarantees that the result carries
        // exactly the supported parameters: unknown parameters are dropped and
        // missing ones fall back to their default value.
        $defaults = $this->defaultUrlParameters();
        $normalized = array_merge($defaults, array_intersect_key($url_parameters, $defaults));

        $parameters = new ParameterBag($normalized);

        $today = \Minz\Time::relative('today midnight');
        $period_days = self::STREAM_PERIOD_DAYS - 1;
        $oldest = \Minz\Time::relative("-{$period_days} days midnight");
        $at = $parameters->getDatetime('at', $today, 'Y-m-d');
        $at = min(max($at, $oldest), $today);
        $normalized['at'] = $at->format('Y-m-d');

        $days = $parameters->getInteger('days', 1);
        $days = min(max($days, 1), 7);
        $normalized['days'] = (string) $days;

        $status = $parameters->getString('status', 'all');
        if (!in_array($status, self::STREAM_STATUSES)) {
            $status = 'all';
        }
        $normalized['status'] = $status;

        $normalized['with_dismissed'] = $parameters->getBoolean('with_dismissed') ? '1' : '';

        $normalized['q'] = trim($parameters->getString('q', ''));

        $source_id = $parameters->getString('source', '');
        $source = $source_id ? Collection::find($source_id) : null;

        if (!$source || !$this->stream()?->hasSource($source)) {
            $source_id = '';
        }

        $normalized['source'] = $source_id;

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
