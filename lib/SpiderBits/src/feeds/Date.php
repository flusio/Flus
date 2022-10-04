<?php

namespace SpiderBits\feeds;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Date
{
    /**
     * Parse the given string date
     *
     * @param string $string_date
     *
     * @return \DateTime|boolean
     *     Return the parsed DateTime, or false if the string cannot be parsed.
     */
    public static function parse($string_date)
    {
        // The list is inspired by feed-io, but the duplicated formats have
        // been removed.
        // @see https://github.com/alexdebril/feed-io/blob/main/src/FeedIo/Rule/DateTimeBuilder.php
        $date_formats = [
            \DateTimeInterface::RFC2822, // RSS
            \DateTimeInterface::RFC3339, // Atom
            \DateTimeInterface::RFC3339_EXTENDED,
            'Y-m-d\TH:i:s.uP',
            'Y-m-d\TH:i:s.uvP',
            'Y-m-d\TH:i:sP',
            'Y-m-d\TH:i:s',
            'Y-m-d\TH:iP',
            'Y-m-d H:i:s.uP',
            'Y-m-d H:i:s.uvP',
            'Y-m-d H:i:sP',
            'Y-m-d H:i:s',
            'Y-m-d H:iP',
            'Y-m-d',
            'd/m/Y',
            'D, d M Y H:i O',
            'D M d Y H:i:s e',
            '*, m#d#Y - H:i',
            'D, d M Y H:i:s \U\T',
            '*, d M* Y H:i:s e',
            '*, d M Y',
        ];

        foreach ($date_formats as $format) {
            $date = \DateTime::createFromFormat($format, $string_date);
            if ($date) {
                return $date;
            }
        }

        return false;
    }
}
