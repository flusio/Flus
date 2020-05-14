<?php

namespace flusio\utils;

class BeltTest extends \PHPUnit\Framework\TestCase
{
    public function testStartsWith()
    {
        $string = 'foobar';
        $substring = 'foo';

        $result = Belt::startsWith($string, $substring);

        $this->assertTrue($result);
    }

    public function testStartsWithWhenSubstringIsEmpty()
    {
        $string = 'foobar';
        $substring = '';

        $result = Belt::startsWith($string, $substring);

        $this->assertTrue($result);
    }

    public function testStartsWithWhenBothAreEmpty()
    {
        $string = '';
        $substring = '';

        $result = Belt::startsWith($string, $substring);

        $this->assertTrue($result);
    }

    public function testStartsWithWhenItDoesntStartsWithSubstring()
    {
        $string = 'foobar';
        $substring = 'spam';

        $result = Belt::startsWith($string, $substring);

        $this->assertFalse($result);
    }

    public function testStartsWithWhenSubstringIsLonger()
    {
        $string = 'foobar';
        $substring = 'foobarspam';

        $result = Belt::startsWith($string, $substring);

        $this->assertFalse($result);
    }

    public function testEndsWith()
    {
        $string = 'foobar';
        $substring = 'bar';

        $result = Belt::endsWith($string, $substring);

        $this->assertTrue($result);
    }

    public function testEndsWithWhenSubstringIsEmpty()
    {
        $string = 'foobar';
        $substring = '';

        $result = Belt::endsWith($string, $substring);

        $this->assertTrue($result);
    }

    public function testEndsWithWhenBothAreEmpty()
    {
        $string = '';
        $substring = '';

        $result = Belt::endsWith($string, $substring);

        $this->assertTrue($result);
    }

    public function testEndsWithWhenItDoesntEndsWithSubstring()
    {
        $string = 'foobar';
        $substring = 'spam';

        $result = Belt::endsWith($string, $substring);

        $this->assertFalse($result);
    }

    public function testEndsWithWhenSubstringIsLonger()
    {
        $string = 'foobar';
        $substring = 'spamfoobar';

        $result = Belt::endsWith($string, $substring);

        $this->assertFalse($result);
    }
}
