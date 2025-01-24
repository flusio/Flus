<?php

namespace App\http;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class RateLimitError extends FetcherError
{
    public function __construct(string $url)
    {
        parent::__construct("Rate limit reached for URL {$url}");
    }
}
