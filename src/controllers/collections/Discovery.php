<?php

namespace App\controllers\collections;

use Minz\Request;
use Minz\Response;
use App\controllers\BaseController;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Discovery extends BaseController
{
    /**
     * Redirect to the discovery page
     *
     * @response 302 /discovery
     */
    public function show(Request $request): Response
    {
        return Response::redirect('discovery');
    }
}
