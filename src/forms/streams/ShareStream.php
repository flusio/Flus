<?php

namespace App\forms\streams;

use App\forms\BaseForm;
use App\forms\traits;
use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class ShareStream extends BaseForm
{
    use traits\ShareRecipient;

    protected function isRecipientOwner(models\User $user): bool
    {
        $stream = $this->optionAs('stream', models\Stream::class);
        return $stream->user_id === $user->id;
    }

    protected function isAlreadySharedWith(models\User $user): bool
    {
        $stream = $this->optionAs('stream', models\Stream::class);
        return $stream->sharedWith($user);
    }

    protected function recipientIsOwnerError(): string
    {
        return _('You can’t share access with the owner of the stream.');
    }

    protected function alreadySharedError(): string
    {
        return _('The stream is already shared with this user.');
    }
}
