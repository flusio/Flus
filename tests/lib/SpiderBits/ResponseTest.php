<?php

namespace SpiderBits;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testFromText(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain\r
        \r
        Hello World!
        TEXT;

        $response = Response::fromText($text);

        $this->assertSame(200, $response->status);
        $this->assertSame('text/plain', $response->headers['content-type']);
        $this->assertSame('Hello World!', $response->data);
    }

    public function testFromTextWithNoContent(): void
    {
        $text = <<<TEXT
        HTTP/2 204 No content\r
        Content-Type: text/plain\r
        \r
        TEXT;

        $response = Response::fromText($text);

        $this->assertSame(204, $response->status);
        $this->assertSame('text/plain', $response->headers['content-type']);
        $this->assertSame('', $response->data);
    }

    public function testFromTextWithNoContentAndNoFinalEmptyLine(): void
    {
        // Even 204 response should contain a final empty line. We just try to
        // be more robust than the norm.
        $text = <<<TEXT
        HTTP/2 204 No content\r
        Content-Type: text/plain\r\n
        TEXT;

        $response = Response::fromText($text);

        $this->assertSame(204, $response->status);
        $this->assertSame('text/plain', $response->headers['content-type']);
        $this->assertSame('', $response->data);
    }

    public function testFromTextWithNoStatusCode(): void
    {
        $text = <<<TEXT
        Content-Type: text/plain\r
        \r
        Hello World!
        TEXT;

        $response = Response::fromText($text);

        $this->assertSame(0, $response->status);
        $this->assertSame('text/plain', $response->headers['content-type']);
        $this->assertSame('Hello World!', $response->data);
    }

    public function testFromTextWithOnlyLfEndOfLine(): void
    {
        // The specs recommends to use CRLF (\r\n), but let’s try to avoid
        // potential issues with bad students.
        // \n are implicit.
        $text = <<<TEXT
        HTTP/2 200 OK
        Content-Type: text/plain

        Hello World!
        TEXT;

        $response = Response::fromText($text);

        $this->assertSame(200, $response->status);
        $this->assertSame('text/plain', $response->headers['content-type']);
        $this->assertSame('Hello World!', $response->data);
    }

    public function testFromTextWithEmptyLineInBody(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain\r
        \r
        Hello\r
        \r
        World!
        TEXT;

        $response = Response::fromText($text);

        $this->assertSame(200, $response->status);
        $this->assertSame('text/plain', $response->headers['content-type']);
        $this->assertSame("Hello\r\n\r\nWorld!", $response->data);
    }

    public function testToString(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain\r
        \r
        Hello World!
        TEXT;
        $response = Response::fromText($text);

        $response_as_string = (string)$response;

        $this->assertSame($text, $response_as_string);
    }

    public function testHeadersWithMultipleValues(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain\r
        Accept-Language: fr; q=1.0
        Accept-Language: en; q=0.5
        \r
        Hello World!
        TEXT;

        $response = Response::fromText($text);

        $this->assertSame('fr; q=1.0, en; q=0.5', $response->headers['accept-language']);
    }

    public function testHeader(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain\r
        \r
        Hello World!
        TEXT;
        $response = Response::fromText($text);

        $header = $response->header('Content-Type');

        $this->assertSame('text/plain', $header);
    }

    public function testHeaderIsCaseInsensitive(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain\r
        \r
        Hello World!
        TEXT;
        $response = Response::fromText($text);

        $header = $response->header('CoNtEnT-TyPe');

        $this->assertSame('text/plain', $header);
    }

    public function testHeaderReturnsDefaultValueIfMissing(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        \r
        Hello World!
        TEXT;
        $response = Response::fromText($text);

        $header = $response->header('content-type', 'text/html');

        $this->assertSame('text/html', $header);
    }

    public function testUtf8Data(): void
    {
        $content = 'Test ëéàçï';
        $content = mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain; charset="ISO-8859-1"\r
        \r
        {$content}
        TEXT;
        $response = Response::fromText($text);
        $encoding = $response->encoding();
        $this->assertSame('ISO-8859-1', $encoding);

        $data = $response->utf8Data();

        $this->assertSame('Test ëéàçï', $data);
    }

    public function testUtf8DataWithUnsupportedEncoding(): void
    {
        $content = 'Test ëéàçï';
        $content = mb_convert_encoding($content, 'ISO-8859-1', 'UTF-8');
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain; charset="Bad-Encoding"\r
        \r
        {$content}
        TEXT;
        $response = Response::fromText($text);
        $encoding = $response->encoding();
        $this->assertSame('Bad-Encoding', $encoding);

        $data = $response->utf8Data();

        $this->assertSame('Test ?????', $data);
    }

    public function testEncodingWithNoSpecifiedEncoding(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain\r
        \r
        Hello World!
        TEXT;
        $response = Response::fromText($text);

        $encoding = $response->encoding();

        $this->assertSame('utf-8', $encoding);
    }

    public function testEncodingWithEncodingInContentType(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/plain; charset="iso-8859-1"\r
        \r
        Hello World!
        TEXT;
        $response = Response::fromText($text);

        $encoding = $response->encoding();

        $this->assertSame('iso-8859-1', $encoding);
    }

    public function testEncodingWithHtmlMetaCharset(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/html\r
        \r
        <html>
            <head>
                <meta charset="iso-8859-1" />
            </head>
        </html>
        TEXT;
        $response = Response::fromText($text);

        $encoding = $response->encoding();

        $this->assertSame('iso-8859-1', $encoding);
    }

    public function testEncodingWithHtmlMetaHttpEquiv(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/html\r
        \r
        <html>
            <head>
                <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
            </head>
        </html>
        TEXT;
        $response = Response::fromText($text);

        $encoding = $response->encoding();

        $this->assertSame('iso-8859-1', $encoding);
    }

    public function testEncodingWithXmlEncoding(): void
    {
        $text = <<<TEXT
        HTTP/2 200 OK\r
        Content-Type: text/xml\r
        \r
        <?xml version="1.0" encoding="iso-8859-1"?>
        <feed>
        </feed>
        TEXT;
        $response = Response::fromText($text);

        $encoding = $response->encoding();

        $this->assertSame('iso-8859-1', $encoding);
    }
}
