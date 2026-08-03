<?php

namespace App\models\links;

use Minz\Database;

/**
 * Add the information about the ability to reach the link URL.
 *
 * A link can be inaccessible to the server (i.e. the server failed to fetch
 * it), while the user is still able to open it. In that case, the user can
 * mark the link as accessible to them.
 *
 * This trait requires Fetchable.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait Reachable
{
    /** @var 'unset'|'ok' */
    #[Database\Column]
    public string $user_fetched_status = 'unset';

    /**
     * Mark the link as accessible to the user.
     */
    public function markAsAccessibleToUser(): void
    {
        $this->user_fetched_status = 'ok';
    }

    /**
     * Reset information that the link is accessible to the user.
     */
    public function resetIsAccessibleToUser(): void
    {
        $this->user_fetched_status = 'unset';
    }

    /**
     * Return whether the link is inaccessible or not.
     *
     * It returns false if the link is inaccessible to the server, but that
     * the user indicated it is accessible to them.
     */
    public function isInaccessible(): bool
    {
        return $this->isInaccessibleToServer() && !$this->isAccessibleToUser();
    }

    /**
     * Return whether the link is inaccessible or not to the server.
     */
    public function isInaccessibleToServer(): bool
    {
        $is_fetched = $this->fetched_at !== null;
        $is_error_code = $this->fetched_code < 200 || $this->fetched_code >= 400;
        return $is_fetched && $is_error_code;
    }

    /**
     * Return whether the link is explicitly marked as accessible to the user.
     */
    public function isAccessibleToUser(): bool
    {
        return $this->user_fetched_status === 'ok';
    }
}
