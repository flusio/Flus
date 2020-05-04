<?php

namespace HelloWorld;

use PHPUnit\Framework\TestCase;

class HelloWorldTest extends TestCase
{
    public function testStringIsHelloWorld()
    {
        $string = 'Hello World';

        $this->assertSame('Hello World', $string);
    }
}
