<?php

namespace App\controllers;

use App\auth;
use App\jobs;
use App\utils;
use Minz\Request;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Showcases extends BaseController
{
    /**
     * Show a showcase page.
     *
     * @request_param string id
     *
     * @response 404 If the id doesn’t exist
     * @response 200 On success
     */
    public function show(Request $request): Response
    {
        $id = $request->parameters->getString('id');
        if ($id === 'navigation') {
            return Response::ok('showcases/show_navigation.phtml');
        } elseif ($id === 'link') {
            return Response::ok('showcases/show_link.phtml');
        } elseif ($id === 'contact') {
            return Response::ok('showcases/show_contact.phtml');
        } elseif ($id === 'reading') {
            return Response::ok('showcases/show_reading.phtml');
        } else {
            return Response::notFound('not_found.phtml');
        }
    }
}
