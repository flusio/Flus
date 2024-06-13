<?php

namespace tests;

/**
 * Provide a fake method, calling the Faker library
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FakerHelper
{
    private static \Faker\Generator $faker;

    #[\PHPUnit\Framework\Attributes\BeforeClass]
    public static function initializeFaker(): void
    {
        self::$faker = \Faker\Factory::create();
    }

    /**
     * Return the result of faker->$factory_name
     *
     * @see https://fakerphp.github.io/
     */
    public function fake(string $factory_name, mixed ...$args): mixed
    {
        $result = self::$faker->$factory_name(...$args);

        if ($result instanceof \DateTime) {
            // We always use DateTimeImmutable but faker is only able to
            // generate DateTime.
            $result = \DateTimeImmutable::createFromMutable($result);
        }

        return $result;
    }

    /**
     * Return the result of faker->unique()->$factory_name
     *
     * @see https://fakerphp.github.io/
     */
    public function fakeUnique(string $factory_name, mixed ...$args): mixed
    {
        $unique_generator = self::$faker->unique();
        $result = $unique_generator->$factory_name(...$args);

        if ($result instanceof \DateTime) {
            // We always use DateTimeImmutable but faker is only able to
            // generate DateTime.
            $result = \DateTimeImmutable::createFromMutable($result);
        }

        return $result;
    }
}
