<?php

namespace App\controllers;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class WellKnown extends BaseController
{
    /**
     * Redirect to the change password page
     *
     * @see https://w3c.github.io/webappsec-change-password-url/
     *
     * @response 302 /my/security
     *
     * @return \Minz\Response
     */
    public function changePassword(): Response
    {
        return Response::redirect('security');
    }
}
