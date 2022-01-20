<?php

namespace flusio\controllers;

use Minz\Response;
use flusio\auth;
use flusio\jobs;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Showcases
{
    /**
     * Show a showcase page.
     *
     * @request_param string id
     *
     * @response 404 If the id doesn’t exist
     * @response 200 On success
     */
    public function show($request)
    {
        $id = $request->param('id');
        if ($id !== 'navigation') {
            return Response::notFound('not_found.phtml');
        }

        return Response::ok('showcases/show_navigation.phtml');
    }
}
