<?php

namespace SpiderBits;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Response
{
    /** @var integer */
    public $status;

    /** @var string */
    public $data;

    /** @var string */
    public $raw_headers;

    /** @var array */
    public $headers;

    /** @var boolean */
    public $success;

    /**
     * @param integer $status
     * @param string $data
     * @param string $raw_headers
     */
    public function __construct($status, $data, $raw_headers)
    {
        $this->status = $status;
        $this->data = $data;
        $this->raw_headers = $raw_headers;
        $this->headers = $this->parseHeaders($raw_headers);
        $this->success = $status >= 200 && $status < 300;
    }

    /**
     * Parse the raw headers (i.e. as a string) and return corresponding array
     * where keys are fields names and values are fields contents.
     *
     * The fields names are lowercased. Multiple fields with the same name are
     * combined into a single field with values seperated by commas.
     *
     * @see https://tools.ietf.org/html/rfc2616
     *
     * @param string $raw_headers
     *
     * @return string[]
     */
    private function parseHeaders($raw_headers)
    {
        $headers = [];
        foreach (explode("\r\n", $raw_headers) as $raw_field) {
            $exploded_field = explode(':', $raw_field, 2);
            if (count($exploded_field) < 2) {
                // this is most probably the status-line or an empty line
                continue;
            }

            $field_name = strtolower($exploded_field[0]);
            $field_content = trim($exploded_field[1]);
            if (isset($headers[$field_name])) {
                $headers[$field_name] .= ',' . $field_content;
            } else {
                $headers[$field_name] = $field_content;
            }
        }
        return $headers;
    }
}
