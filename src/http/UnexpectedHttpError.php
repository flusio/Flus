<?php

namespace App\http;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UnexpectedHttpError extends FetcherError
{
    public function __construct(string $url, \SpiderBits\HttpError $error)
    {
        parent::__construct("HTTP error when fetching URL {$url}: {$error->getMessage()}");
    }
}
