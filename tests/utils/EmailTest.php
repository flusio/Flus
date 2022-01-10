<?php

namespace flusio\utils;

class EmailTest extends \PHPUnit\Framework\TestCase
{
    public function testSanitize()
    {
        $email = " Charlie@Example.com \t";

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('charlie@example.com', $sanitized_email);
    }

    public function testSanitizeWithInternationalizedAddress()
    {
        $email = 'Δοκιμή@Παράδειγμα.δοκιμή';

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('δοκιμή@xn--hxajbheg2az3al.xn--jxalpdlp', $sanitized_email);
    }

    public function testSanitizeWithInvalidAddress()
    {
        $email = 'Not-an-email.com ';

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('not-an-email.com', $sanitized_email);
    }

    public function testSanitizeWithEmptyAddress()
    {
        $email = '';

        $sanitized_email = Email::sanitize($email);

        $this->assertSame('', $sanitized_email);
    }

    public function testValidate()
    {
        $email = 'charlie@example.com';

        $result = Email::validate($email);

        $this->assertTrue($result);
    }

    public function testValidateWithInvalidAddress()
    {
        $email = 'example.com';

        $result = Email::validate($email);

        $this->assertFalse($result);
    }

    public function testValidateWithEmptyAddress()
    {
        $email = '';

        $result = Email::validate($email);

        $this->assertFalse($result);
    }

    public function testValidateWithInternationalizedAddress()
    {
        $email = 'charlie@Παράδειγμα.δοκιμή';

        $result = Email::validate($email);

        $this->assertFalse($result);
    }

    public function testValidateWithSanitizedInternationalizedAddress()
    {
        // it always fails if local part is internationalized
        $email = Email::sanitize('charlie@Παράδειγμα.δοκιμή');

        $result = Email::validate($email);

        $this->assertTrue($result);
    }
}
