<?php

namespace SpiderBits;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Response
{
    public int $status;

    public string $data;

    public string $raw_headers;

    /** @var string[] */
    public array $headers;

    public bool $success;

    /**
     * Construct a Response from a raw string
     */
    public static function fromText(string $raw_response): self
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

    public function __construct(int $status, string $data, string $raw_headers)
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
     */
    public function __toString(): string
    {
        return $this->raw_headers . "\r\n\r\n" . $this->data;
    }

    /**
     * Return a header value
     */
    public function header(string $name, ?string $default = null): ?string
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
     * @return string[]
     */
    public static function parseHeaders(string $raw_headers): array
    {
        $headers = [];

        $raw_fields = preg_split('/\R/', $raw_headers);
        if ($raw_fields === false) {
            return [];
        }

        foreach ($raw_fields as $raw_field) {
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
