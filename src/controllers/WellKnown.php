<?php

namespace flusio\controllers;

use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class WellKnown
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
    public function changePassword()
    {
        return Response::redirect('security');
    }
}
