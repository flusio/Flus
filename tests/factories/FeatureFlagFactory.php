<?php

namespace tests\factories;

use flusio\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\FeatureFlag>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class FeatureFlagFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\FeatureFlag::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'type' => function () use ($faker) {
                return $faker->randomElement(models\FeatureFlag::VALID_TYPES);
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },
        ];
    }
}
