<?php

namespace App\models;

use Minz\Database;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
#[Database\Table(name: 'feature_flags')]
class FeatureFlag
{
    use Database\Recordable;

    public const VALID_TYPES = ['alpha', 'beta'];

    #[Database\Column]
    public int $id;

    #[Database\Column]
    public \DateTimeImmutable $created_at;

    /** @var value-of<self::VALID_TYPES> */
    #[Database\Column]
    public string $type;

    #[Database\Column]
    public string $user_id;

    /**
     * Return the user associated to the feature flag.
     */
    public function user(): User
    {
        return User::require($this->user_id);
    }

    /**
     * Enable a flag for a user.
     */
    public static function enable(string $type, User $user): void
    {
        self::findOrCreateBy([
            'type' => $type,
            'user_id' => $user->id,
        ]);
    }

    /**
     * Disable a flag for a user.
     */
    public static function disable(string $type, User $user): void
    {
        $feature_flag = self::findBy([
            'type' => $type,
            'user_id' => $user->id,
        ]);

        if ($feature_flag) {
            $feature_flag->remove();
        }
    }

    /**
     * Return whether a flag is enabled for the given user
     */
    public static function isEnabled(string $type, User $user): bool
    {
        return self::existsBy([
            'type' => $type,
            'user_id' => $user->id,
        ]);
    }
}
