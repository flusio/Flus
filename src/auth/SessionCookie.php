<?php

namespace App\auth;

use App\models;
use Minz\Response;

/**
 * Manipulate the cookie which stores the browser session token.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class SessionCookie
{
    /**
     * Set the session cookie on the response, expiring with the session token.
     */
    public static function set(Response $response, models\Session $session): void
    {
        $token = $session->token();

        $response->setCookie('session_token', $token->token, [
            'expires' => $token->expired_at->getTimestamp(),
            'samesite' => 'Lax',
        ]);
    }
}
