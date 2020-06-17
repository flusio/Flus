<?php

namespace tests;

/**
 * Provide asserts method for the flash utility.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
trait FlashAsserts
{
    /**
     * Assert the given flash message is set to the given value.
     *
     * @param string $name
     * @param string $expected_value
     * @param string $message (optional)
     */
    public function assertFlash($name, $expected_value, $message = '')
    {
        $value = \flusio\utils\Flash::get($name);
        $this->assertSame($expected_value, $value, $message);
    }
}
