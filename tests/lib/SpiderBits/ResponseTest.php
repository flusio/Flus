<?php

namespace SpiderBits;

class ResponseTest extends \PHPUnit\Framework\TestCase
{
    public function testFromText()
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

    public function testFromTextWithNoContent()
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

    public function testFromTextWithNoStatusCode()
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

    public function testFromTextWithOnlyLfEndOfLine()
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

    public function testFromTextWithEmptyLineInBody()
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

    public function testToString()
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

    public function testHeadersWithMultipleValues()
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

    public function testHeader()
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

    public function testHeaderIsCaseInsensitive()
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

    public function testHeaderReturnsDefaultValueIfMissing()
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
}
