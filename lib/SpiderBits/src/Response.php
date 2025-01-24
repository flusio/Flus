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
     * Return the encoding of the data.
     */
    public function encoding(): string
    {
        /** @var string */
        $content_type = $this->header('content-type', '');
        $is_html = str_contains($content_type, 'text/html');
        $charset = self::extractCharsetFromContentType($content_type);

        if ($charset) {
            $encoding = $charset;
        } elseif ($this->data) {
            $encoding = $this->encodingFromData($this->data, $is_html);
        } else {
            $encoding = 'utf-8';
        }

        // Don't trust websites that declare "iso-8859-1": it should be treated
        // as "windows-1252" according to HTML5 spec!
        // See https://en.wikipedia.org/wiki/Windows-1252
        // Also https://encoding.spec.whatwg.org/#names-and-labels
        if ($is_html && strtolower($encoding) === 'iso-8859-1') {
            $encoding = 'windows-1252';
        }

        return strtolower($encoding);
    }

    /**
     * @param non-empty-string $data
     */
    private function encodingFromData(string $data, bool $is_html): string
    {
        $data = mb_substr($data, 0, 5000); // look only in the first 5000 characters

        $result = preg_match(
            '/^\s*<\?xml\s+(?:(?:.*?)\s)?encoding=[\'"](?P<encoding>.+?)[\'"]/i',
            $data,
            $matches
        );

        if ($result) {
            // The document declares a XML encoding.
            return $matches['encoding'];
        }

        if (!$is_html) {
            // The document isn't declared as HTML, return UTF-8 by default.
            return 'utf-8';
        }

        $dom = Dom::fromText($data);

        $node_charset = $dom->select('//meta/attribute::charset');
        if ($node_charset) {
            // The HTML document declares a meta charset attribute.
            return $node_charset->text();
        }

        $node_http_equiv = $dom->select('//meta[@http-equiv = "Content-Type"]/attribute::content');
        if ($node_http_equiv) {
            $charset = self::extractCharsetFromContentType($node_http_equiv->text());
            if ($charset) {
                // The HTML document declares a meta http-equiv attribute and a charset.
                return $charset;
            }
        }

        return 'utf-8';
    }

    /**
     * Return the data encoded in UTF-8.
     *
     * SpiderBits does its best to convert the data to actual utf8. It gets the
     * encoding from the Content-Type header and/or, for HTML, from the meta
     * tags.
     */
    public function utf8Data(): string
    {
        try {
            return mb_convert_encoding($this->data, 'utf-8', $this->encoding());
        } catch (\ValueError $exception) {
            return mb_convert_encoding($this->data, 'utf-8', 'utf-8');
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

    /**
     * Return the charset contained in the given content type header, if any.
     */
    public static function extractCharsetFromContentType(string $content_type): ?string
    {
        $result = preg_match('/charset=(?P<charset>[\w\-\."]+)/i', $content_type, $matches);

        if ($result !== 1) {
            return null;
        }

        $charset = $matches['charset'];
        $charset = trim($charset, '"');
        return $charset;
    }
}
