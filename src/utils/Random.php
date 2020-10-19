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

    /**
     * Return a random cryptographically secure integer where first bits are
     * the current timestamp in milliseconds and last 20 bits are random.
     *
     * Please note the result is returned as a string.
     *
     * @return string
     */
    public static function timebased()
    {
        $milliseconds = (int)(microtime(true) * 1000);
        $time_part = $milliseconds << 20;
        $random_part = random_int(0, 1048575); // max number on 20 bits
        return strval($time_part | $random_part);
    }
}
