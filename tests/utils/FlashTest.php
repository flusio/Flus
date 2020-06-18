<?php

namespace flusio\utils;

class FlashTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @before
     */
    public function resetSession()
    {
        session_unset();
    }

    public function testGet()
    {
        Flash::set('foo', 'bar');

        $result = Flash::get('foo');

        $this->assertSame('bar', $result);
    }

    public function testGetTwice()
    {
        Flash::set('foo', 'bar');

        $result = Flash::get('foo');
        $this->assertSame('bar', $result);

        $result = Flash::get('foo');
        $this->assertSame('bar', $result);
    }

    public function testGetWithDefaultValue()
    {
        $result = Flash::get('foo', 'bar');

        $this->assertSame('bar', $result);
    }

    public function testGetWithUnset()
    {
        $result = Flash::get('foo');

        $this->assertNull($result);
    }

    public function testGetAfterPop()
    {
        Flash::set('foo', 'bar');

        $result = Flash::pop('foo');
        $this->assertSame('bar', $result);

        $result = Flash::get('foo');
        $this->assertNull($result);
    }

    public function testPop()
    {
        Flash::set('foo', 'bar');

        $result = Flash::pop('foo');

        $this->assertSame('bar', $result);
    }

    public function testPopTwice()
    {
        Flash::set('foo', 'bar');

        $result = Flash::pop('foo');
        $this->assertSame('bar', $result);

        $result = Flash::pop('foo');
        $this->assertNull($result);
    }

    public function testPopWithDefaultValue()
    {
        $result = Flash::pop('foo', 'bar');

        $this->assertSame('bar', $result);
    }

    public function testPopWithUnset()
    {
        $result = Flash::pop('foo');

        $this->assertNull($result);
    }

    public function testPopAfterGet()
    {
        Flash::set('foo', 'bar');

        $result = Flash::get('foo');
        $this->assertSame('bar', $result);

        $result = Flash::pop('foo');
        $this->assertSame('bar', $result);
    }
}
