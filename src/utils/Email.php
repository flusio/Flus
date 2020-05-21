<?php

namespace flusio\utils;

/**
 * Provide utility functions related to email address.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Email
{
    /**
     * Sanitize an email address (trim, lowercase and punycode)
     */
    public static function sanitize($email)
    {
        return strtolower(self::emailToPunycode(trim($email)));
    }

    /**
     * @see https://en.wikipedia.org/wiki/Punycode
     *
     * @param string $email
     *
     * @param string
     */
    public static function emailToPunycode($email)
    {
        $at_position = strrpos($email, '@');

        if ($at_position === false || !function_exists('idn_to_ascii')) {
            return $email;
        }

        $domain = substr($email, $at_position + 1);
        $domain = idn_to_ascii($domain, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
        if ($domain !== false) {
            $email = substr($email, 0, $at_position + 1) . $domain;
        }

        return $email;
    }

    /**
     * Return wheter or not an email address is valid.
     *
     * @param string $email
     *
     * @return boolean
     */
    public static function validate($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}
