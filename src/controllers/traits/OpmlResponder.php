<?php

namespace App\controllers\traits;

use App\utils;
use Minz\Response;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait OpmlResponder
{
    /**
     * Return a response to download the given OPML content. The name is used
     * to build the filename (e.g. 2026-10-27_Flus_sources.opml.xml).
     */
    protected function opmlResponse(string $opml, string $name): Response
    {
        $filename_date = \Minz\Time::now()->format('Y-m-d');
        $filename_brand = utils\Belt::slugify(\App\Configuration::$application['brand']);
        $filename_name = utils\Belt::slugify($name);
        $filename = "{$filename_date}_{$filename_brand}_{$filename_name}.opml.xml";

        $response = Response::text(200, $opml);
        $response->setHeader('Content-Type', 'text/x-opml');
        $response->setHeader('Content-Disposition', "inline; filename=\"{$filename}\"");

        return $response;
    }
}
