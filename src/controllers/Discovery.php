<?php

namespace flusio\controllers;

use Minz\Request;
use Minz\Response;
use flusio\models;
use flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Discovery
{
    /**
     * Show the discovery page
     *
     * @response 200
     */
    public function show(Request $request): Response
    {
        $topics = models\Topic::listAll();
        $topics = utils\Sorter::localeSort($topics, 'label');

        return Response::ok('discovery/show.phtml', [
            'topics' => $topics,
        ]);
    }
}
