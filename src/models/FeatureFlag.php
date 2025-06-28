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
        $user = User::find($this->user_id);

        if (!$user) {
            throw new \Exception("FeatureFlag #{$this->id} has invalid user.");
        }

        return $user;
    }

    /**
     * Enable a flag for a user.
     */
    public static function enable(string $type, string $user_id): void
    {
        self::findOrCreateBy([
            'type' => $type,
            'user_id' => $user_id,
        ]);
    }

    /**
     * Disable a flag for a user.
     */
    public static function disable(string $type, string $user_id): void
    {
        $feature_flag = self::findBy([
            'type' => $type,
            'user_id' => $user_id,
        ]);

        if ($feature_flag) {
            $feature_flag->remove();
        }
    }

    /**
     * Return whether a flag is enabled for the given user
     */
    public static function isEnabled(string $type, string $user_id): bool
    {
        return self::existsBy([
            'type' => $type,
            'user_id' => $user_id,
        ]);
    }
}
