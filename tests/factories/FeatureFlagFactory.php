<?php

namespace tests\factories;

use App\models;
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
            'created_at' => fn() => $faker->dateTime,

            'type' => fn() => $faker->randomElement(models\FeatureFlag::VALID_TYPES),

            'user_id' => fn() => UserFactory::create()->id,
        ];
    }
}
