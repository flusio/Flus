<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\forms\checks;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditCollectionImage extends BaseForm
{
    #[Form\Field]
    #[Validable\Presence(
        message: new Translatable('The file is required.'),
    )]
    #[checks\Image(types: ['png', 'jpg', 'jpeg', 'webp'])]
    public ?\Minz\File $image = null;
}
