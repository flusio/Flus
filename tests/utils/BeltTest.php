<?php

namespace flusio\utils;

class BeltTest extends \PHPUnit\Framework\TestCase
{
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

    public function testCut()
    {
        $string = 'foobarbaz';
        $size = 3;

        $new_string = Belt::cut($string, $size);

        $this->assertSame('foo', $new_string);
    }

    public function testCutHandlesMultiBytesStringCorrectly()
    {
        // U+0800 is encoded on 3 bytes in UTF-8, usual PHP functions such as
        // substr cannot work properly with it
        $string = "\u{0800}\u{0800}\u{0800}\u{0800}\u{0800}";
        $size = 3;

        $new_string = Belt::cut($string, $size);

        $this->assertSame("\u{0800}\u{0800}\u{0800}", $new_string);
    }

    public function testCutWithNegativeSize()
    {
        $string = 'foobarbaz';
        $size = -3;

        $new_string = Belt::cut($string, $size);

        $this->assertSame('foobar', $new_string);
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

    public function testHostReturnsEmptyStringIfEmpty()
    {
        $url = '';

        $host = Belt::host($url);

        $this->assertSame('', $host);
    }

    public function testHostReturnsEmptyStringIfInvalid()
    {
        $url = 'https:///';

        $host = Belt::host($url);

        $this->assertSame('', $host);
    }

    public function testRemoveScheme()
    {
        $url = 'https://flus.fr';

        $without_scheme = Belt::removeScheme($url);

        $this->assertSame('flus.fr', $without_scheme);
    }

    public function testRemoveSchemeWithHttp()
    {
        $url = 'http://flus.fr';

        $without_scheme = Belt::removeScheme($url);

        $this->assertSame('flus.fr', $without_scheme);
    }

    public function testRemoveSchemeWithoutScheme()
    {
        $url = 'flus.fr';

        $without_scheme = Belt::removeScheme($url);

        $this->assertSame('flus.fr', $without_scheme);
    }

    public function testRemoveSchemeWithEmptyString()
    {
        $url = '';

        $without_scheme = Belt::removeScheme($url);

        $this->assertSame('', $without_scheme);
    }

    public function testFilenameToSubpath()
    {
        $filename = 'abcdefghijklmnop.png';

        $subpath = Belt::filenameToSubpath($filename);

        $this->assertSame('abc/def/ghi', $subpath);
    }

    public function testFilenameToSubpathRemovesDot()
    {
        $filename = 'abcdef.png';

        $subpath = Belt::filenameToSubpath($filename);

        $this->assertSame('abc/def/png', $subpath);
    }

    public function testFilenameToSubpathReturnsEmptyStringWithLessThanNineCharacters()
    {
        $filename = 'abcde.png';

        $subpath = Belt::filenameToSubpath($filename);

        $this->assertSame('', $subpath);
    }
}
