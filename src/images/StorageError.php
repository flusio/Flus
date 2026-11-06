<?php

namespace App\images;

/**
 * Exception raised by the Storage when an image cannot be obtained
 * (e.g. it cannot be fetched or read).
 *
 * @see \App\images\Storage
 *
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class StorageError extends \RuntimeException
{
}
