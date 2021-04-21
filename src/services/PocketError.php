<?php

namespace flusio\services;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class PocketError extends \RuntimeException
{
    public function __construct($code, $message = '')
    {
        $this->code = intval($code);
        if ($this->code === 199) {
            $message = _('Pocket error: Pocket has issues, please try later (error code %d).');
        } elseif ($this->code === 158) {
            $message = _('Pocket error: you rejected the authorization (error code %d).');
        } elseif (!$message) {
            $message = _('Pocket error: bad configuration, please contact the support (error code %d).');
        }

        parent::__construct(vsprintf($message, $code));
    }
}
