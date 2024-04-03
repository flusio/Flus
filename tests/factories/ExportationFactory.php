<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Exportation>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ExportationFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Exportation::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'created_at' => function () use ($faker) {
                return $faker->dateTime;
            },

            'status' => function () use ($faker) {
                return $faker->randomElement(models\Exportation::VALID_STATUSES);
            },

            'user_id' => function () {
                return UserFactory::create()->id;
            },
        ];
    }
}
