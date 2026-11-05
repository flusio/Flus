<?php

namespace App\services;

/**
 * Exception raised by the Image service when an image cannot be obtained
 * (e.g. it cannot be fetched or read).
 *
 * @see \App\services\Image
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ImageError extends \RuntimeException
{
}
