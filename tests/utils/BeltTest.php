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

    public function testContains()
    {
        $string = 'foobar';
        $substring = 'ooba';

        $result = Belt::contains($string, $substring);

        $this->assertTrue($result);
    }

    public function testContainsWhenStringIsAtTheStart()
    {
        $string = 'foobar';
        $substring = 'foo';

        $result = Belt::contains($string, $substring);

        $this->assertTrue($result);
    }

    public function testContainsWhenStringIsAtTheEnd()
    {
        $string = 'foobar';
        $substring = 'bar';

        $result = Belt::contains($string, $substring);

        $this->assertTrue($result);
    }

    public function testContainsWhenStringsAreTheSame()
    {
        $string = 'foobar';
        $substring = 'foobar';

        $result = Belt::contains($string, $substring);

        $this->assertTrue($result);
    }

    public function testContainsWhenSubstringIsEmpty()
    {
        $string = 'foobar';
        $substring = '';

        $result = Belt::contains($string, $substring);

        $this->assertTrue($result);
    }

    public function testContainsWhenBothAreEmpty()
    {
        $string = '';
        $substring = '';

        $result = Belt::contains($string, $substring);

        $this->assertTrue($result);
    }

    public function testContainsReturnsFalseIfDoesntContainsSubstring()
    {
        $string = 'foobar';
        $substring = 'spam';

        $result = Belt::contains($string, $substring);

        $this->assertFalse($result);
    }

    public function testContainsReturnsFalseIfSubstringIsLonger()
    {
        $string = 'foobar';
        $substring = 'foobarfoo';

        $result = Belt::contains($string, $substring);

        $this->assertFalse($result);
    }

    public function testStripsStart()
    {
        $string = 'foobar';
        $substring = 'foo';

        $result = Belt::stripsStart($string, $substring);

        $this->assertSame('bar', $result);
    }

    public function testStripsStartDoesNotStartWith()
    {
        $string = 'foobar';
        $substring = 'bar';

        $result = Belt::stripsStart($string, $substring);

        $this->assertSame('foobar', $result);
    }

    public function testStripsEnd()
    {
        $string = 'foobar';
        $substring = 'bar';

        $result = Belt::stripsEnd($string, $substring);

        $this->assertSame('foo', $result);
    }

    public function testStripsEndDoesNotEndWith()
    {
        $string = 'foobar';
        $substring = 'foo';

        $result = Belt::stripsEnd($string, $substring);

        $this->assertSame('foobar', $result);
    }

    public function testHost()
    {
        $url = 'https://flus.fr';

        $host = Belt::host($url);

        $this->assertSame('flus.fr', $host);
    }

    public function testHostDoesNotReturnWwwDot()
    {
        $url = 'https://www.flus.fr';

        $host = Belt::host($url);

        $this->assertSame('flus.fr', $host);
    }

    public function testHostConvertsIdnaEncodedToUnicode()
    {
        $url = 'https://xn--dtour-bsa.studio/';

        $host = Belt::host($url);

        $this->assertSame('détour.studio', $host);
    }

    public function testHostReturnsEmptyStringIfInvalid()
    {
        $url = 'https:///';

        $host = Belt::host($url);

        $this->assertSame('', $host);
    }
}
