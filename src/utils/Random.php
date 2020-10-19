<?php

namespace flusio\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Random
{
    /**
     * Return a random cryptographically secure string containing characters in
     * range 0-9a-f.
     *
     * @see https://paragonie.com/blog/2015/07/how-safely-generate-random-strings-and-integers-in-php
     *
     * @param integer $length
     *
     * @return string
     */
    public static function hex($length)
    {
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be a positive integer');
        }

        $string = '';
        $alphabet = '0123456789abcdef';
        $alphamax = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; ++$i) {
            $string .= $alphabet[random_int(0, $alphamax)];
        }

        return $string;
    }
}
