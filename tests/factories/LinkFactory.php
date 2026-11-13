<?php

namespace tests\factories;

use App\models;
use Minz\Database;

/**
 * @extends Database\Factory<models\Link>
 *
 * @phpstan-import-type ModelValues from Database\Recordable
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class LinkFactory extends Database\Factory
{
    public static function model(): string
    {
        return models\Link::class;
    }

    public static function values(): array
    {
        $faker = \Faker\Factory::create();

        return [
            'id' => fn(): string => \Minz\Random::timebased(),

            'created_at' => fn(): \DateTimeImmutable => \DateTimeImmutable::createFromInterface($faker->dateTime),

            'title' => fn() => $faker->words(3, true),

            'url' => fn() => $faker->url,

            'url_feeds' => [],

            'is_hidden' => fn() => $faker->boolean,

            'owner_id' => fn(): string => UserFactory::create()->owner()->id,

            'source_type' => '',
        ];
    }

    /**
     * @param ModelValues $values
     *
     * @return ModelValues
     */
    protected static function beforeBuild(array $values): array
    {
        if (!array_key_exists('user_id', $values)) {
            $owner_id = $values['owner_id'];
            assert(is_string($owner_id));

            // The owner may be the system owner, so the link has no user.
            $values['user_id'] = models\UserOwner::find($owner_id)?->user_id;
        }

        return $values;
    }
}
