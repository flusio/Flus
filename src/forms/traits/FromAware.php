<?php

namespace App\forms\traits;

use App\utils;
use Minz\Form;
use Minz\Request;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FromAware
{
    private string $from = '';

    #[Form\OnHandleRequest]
    public function setFrom(Request $request): void
    {
        $from = utils\RequestHelper::from($request);
        $from = \SpiderBits\Url::absolutize($from, \Minz\Url::baseUrl());
        $this->from = $from;
    }
}
