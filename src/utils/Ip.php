<?php

namespace App\utils;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Ip
{
    public static function mask(string $ip): string
    {
        if (str_contains($ip, '.')) {
            $result = preg_replace('/\.\d*$/', '.XXX', $ip);
        } else {
            $result = preg_replace('/[\da-f]*:[\da-f]*$/', 'XXXX:XXXX', $ip);
        }

        if ($result === null) {
            $result = '';
        }

        return $result;
    }
}
