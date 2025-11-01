<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Validable;

/**
 * @extends BaseForm<models\Collection>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends BaseForm
{
    #[Form\Field(transform: 'trim')]
    public string $name = '';

    #[Form\Field(transform: 'trim')]
    public string $description = '';

    /** @var string[] */
    #[Form\Field(bind: false)]
    public array $topic_ids = [];

    #[Form\Field]
    public bool $is_public = false;

    public int $collection_name_max_length = models\Collection::NAME_MAX_LENGTH;

    /**
     * @return models\Topic[]
     */
    public function topicsValues(): array
    {
        $topics = models\Topic::listAll();
        return utils\Sorter::localeSort($topics, 'label');
    }

    /**
     * Find a topic by its id from the topics values.
     */
    public function findTopic(string $topic_id): ?models\Topic
    {
        $topics_values = $this->topicsValues();

        foreach ($topics_values as $topic) {
            if ($topic->id === $topic_id) {
                return $topic;
            }
        }

        return null;
    }

    public function isTopicSelected(models\Topic $topic): bool
    {
        return in_array($topic->id, $this->topic_ids);
    }

    /**
     * @throw new \RuntimeException
     *     Raised if a selected topic_id doesn't match an existing topic.
     *
     * @return models\Topic[]
     */
    public function selectedTopics(): array
    {
        $selected_topics = [];

        foreach ($this->topic_ids as $topic_id) {
            $topic = $this->findTopic($topic_id);

            if (!$topic) {
                throw new \RuntimeException("Topic {$topic_id} does not exist.'");
            }

            $selected_topics[] = $topic;
        }

        return $selected_topics;
    }

    /**
     * Check that the selected topics are part of the topics values.
     */
    #[Validable\Check]
    public function checkSelectedTopics(): void
    {
        try {
            $this->selectedTopics();
        } catch (\RuntimeException $e) {
            $this->addError(
                'topic_ids',
                'invalidTopic',
                _('One of the associated topic doesn’t exist.'),
            );
        }
    }
}
