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
     * Construct a Response from a raw string
     *
     * @param string $raw_response
     *
     * @return \SpiderBits\Response
     */
    public static function fromText($raw_response)
    {
        $result = preg_match('/^(?P<headers>.+?)\R\R(?P<body>.+)?$/sm', $raw_response, $matches);
        if (!$result) {
            $headers = $raw_response;
            $body = '';
        } else {
            $headers = $matches['headers'];
            $body = $matches['body'] ?? '';
        }

        preg_match('/^HTTP\/\d+(\.\d+)?\s+(?P<status>\d{3}).*$/m', $headers, $matches);
        if (isset($matches['status'])) {
            $status = intval($matches['status']);
        } else {
            $status = 0;
        }

        return new self($status, $body, $headers);
    }

    /**
     * @param integer $status
     * @param string $data
     * @param string $raw_headers
     */
    public function __construct($status, $data, $raw_headers)
    {
        $this->status = $status;
        $this->data = $data;
        $this->raw_headers = trim($raw_headers);
        $this->headers = self::parseHeaders($this->raw_headers);
        $this->success = $status >= 200 && $status < 300;
    }

    /**
     * Return the Response as a string which can be parsed by the fromText()
     * method.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->raw_headers . "\r\n\r\n" . $this->data;
    }

    /**
     * Return a header value
     *
     * @param string $name The name of the parameter to get (case insensitive)
     * @param mixed $default A default value to return if the parameter doesn't exist
     *
     * @return mixed
     */
    public function header($name, $default = null)
    {
        $name = strtolower($name);
        if (isset($this->headers[$name])) {
            return $this->headers[$name];
        } else {
            return $default;
        }
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
    public static function parseHeaders($raw_headers)
    {
        $headers = [];
        foreach (preg_split('/\R/', $raw_headers) as $raw_field) {
            $exploded_field = explode(':', $raw_field, 2);
            if (count($exploded_field) < 2) {
                // this is most probably the status-line or an empty line
                continue;
            }

            $field_name = strtolower($exploded_field[0]);
            $field_content = trim($exploded_field[1]);
            if (isset($headers[$field_name])) {
                $headers[$field_name] .= ', ' . $field_content;
            } else {
                $headers[$field_name] = $field_content;
            }
        }
        return $headers;
    }
}
