<?php

namespace SpiderBits;

/**
 * The DOM extractor, pure juice.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class DomExtractor
{
    /**
     * Return the title of the DOM document.
     *
     * @param \SpiderBits\Dom $dom
     *
     * @return string
     */
    public static function title($dom)
    {
        $title = $dom->select('/html/head/title');
        if ($title) {
            return $title->text();
        } else {
            return '';
        }
    }

    /**
     * Return the main content of the DOM document.
     *
     * @param \SpiderBits\Dom $dom
     *
     * @return string
     */
    public static function content($dom)
    {
        $body = $dom->select('//body');
        if ($body) {
            $main = $body->select('//main');
            if (!$main) {
                $main = $body->select('//*[@id = "main"]');
            }

            if ($main) {
                return $main->text();
            } else {
                return $body->text();
            }
        } else {
            return '';
        }
    }
}
