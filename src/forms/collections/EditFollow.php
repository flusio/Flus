<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use Minz\Form;
use Minz\Validable;

/**
 * @phpstan-import-type Parameters from \Minz\ParameterBag
 *
 * @extends BaseForm<models\FollowedCollection>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditFollow extends BaseForm
{
    #[Form\Field]
    public string $time_filter = 'normal';

    /** @var string[] */
    #[Form\Field(bind: false)]
    public array $stream_ids = [];

    #[Form\Field(bind: false, transform: 'trim')]
    public string $new_stream_name = '';

    public int $stream_name_max_length = models\Stream::NAME_MAX_LENGTH;

    /**
     * @param array<string, mixed> $default_values
     * @param Parameters $options
     */
    public function __construct(
        array $default_values = [],
        ?models\FollowedCollection $model = null,
        array $options = [],
    ) {
        if ($model && !isset($default_values['stream_ids'])) {
            $stream_ids = array_column($model->streams(), 'id');
            $default_values['stream_ids'] = $stream_ids;
        }

        parent::__construct($default_values, $model, $options);
    }

    /**
     * Return the streams to display in the form.
     *
     * @return models\Stream[]
     */
    public function streams(): array
    {
        $user = $this->optionAs('user', models\User::class);
        return $user->streams();
    }

    public function isStreamSelected(models\Stream $stream): bool
    {
        return in_array($stream->id, $this->stream_ids);
    }

    /**
     * Return the streams selected via the stream_ids attribute.
     *
     * @throws \RuntimeException
     *     Raised if a selected stream_id doesn't match a stream of the user.
     *
     * @return models\Stream[]
     */
    public function selectedStreams(): array
    {
        $streams_by_ids = array_column($this->streams(), null, 'id');

        $selected_streams = [];

        foreach ($this->stream_ids as $stream_id) {
            if (!isset($streams_by_ids[$stream_id])) {
                throw new \RuntimeException("Stream {$stream_id} does not exist.");
            }

            $selected_streams[] = $streams_by_ids[$stream_id];
        }

        return $selected_streams;
    }

    /**
     * Find a stream by its name from the streams values.
     */
    public function findStream(string $stream_name): ?models\Stream
    {
        foreach ($this->streams() as $stream) {
            if ($stream->name === $stream_name) {
                return $stream;
            }
        }

        return null;
    }

    /**
     * Return the stream designated by the new_stream_name attribute, if any.
     *
     * The existing stream is returned if the user already owns one with this
     * name, so a name typed instead of being checked doesn't create a
     * duplicate. Otherwise, a new stream is initialized (but not saved).
     */
    public function namedStream(): ?models\Stream
    {
        if (!$this->new_stream_name) {
            return null;
        }

        $stream = $this->findStream($this->new_stream_name);

        if ($stream) {
            return $stream;
        }

        $user = $this->optionAs('user', models\User::class);

        $stream = $user->initStream();
        $stream->name = $this->new_stream_name;

        return $stream;
    }

    /**
     * Check that the selected streams are owned by the user.
     */
    #[Validable\Check]
    public function checkSelectedStreams(): void
    {
        try {
            $this->selectedStreams();
        } catch (\RuntimeException $e) {
            $this->addError(
                'stream_ids',
                'invalidStream',
                _('One of the selected streams doesn’t exist.'),
            );
        }
    }

    /**
     * Check that the stream to create is valid (i.e. that its name is not too
     * long).
     */
    #[Validable\Check]
    public function checkNamedStream(): void
    {
        $stream = $this->namedStream();

        if (!$stream) {
            return;
        }

        if ($stream->validate()) {
            return;
        }

        $this->addErrors($stream->errors(format: false), 'new_stream_name');
    }
}
