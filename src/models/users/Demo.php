<?php

namespace App\models\users;

use App\utils;

/**
 * Add methods to manage the demo account.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Demo
{
    public const DEMO_EMAIL = 'demo@flus.fr';
    public const DEMO_USERNAME = 'Alix Hambourg';
    public const DEMO_PASSWORD = 'demo';

    public static function demoUser(): self
    {
        return self::findOrCreateBy([
            'email' => self::DEMO_EMAIL,
        ], [
            'id' => \Minz\Random::timebased(),
            'username' => self::DEMO_USERNAME,
            'password_hash' => self::passwordHash(self::DEMO_PASSWORD),
            'locale' => utils\Locale::currentLocale(),
            'validated_at' => \Minz\Time::now(),
        ]);
    }

    /**
     * Return whether the user is the demo account.
     *
     * It can return true even if demo is not enabled.
     */
    public function isDemoUser(): bool
    {
        return $this->email === self::DEMO_EMAIL;
    }
}
