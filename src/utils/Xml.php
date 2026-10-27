<?php

namespace App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Xml
{
    /**
     * Return a formatted version of a XML string.
     *
     * If an error occurs, the initial XML is returned as is.
     */
    public static function format(string $xml_as_string): string
    {
        $dom_document = new \DOMDocument();
        $dom_document->preserveWhiteSpace = false;
        $dom_document->formatOutput = true;
        $result = @$dom_document->loadXML($xml_as_string);
        if (!$result) {
            return $xml_as_string;
        }

        $formatted_xml = $dom_document->saveXML();
        if (!$formatted_xml) {
            return $xml_as_string;
        }

        return $formatted_xml;
    }
}
