<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\CollectionToTopic>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CollectionToTopicFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\CollectionToTopic::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'collection_id' => function () {
                return CollectionFactory::create()->id;
            },

            'topic_id' => function () {
                return TopicFactory::create()->id;
            },
        ];
    }
}
