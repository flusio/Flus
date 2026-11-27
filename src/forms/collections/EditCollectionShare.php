<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use Minz\Form;

/**
 * Change the access type of a user to a collection.
 *
 * @extends BaseForm<models\CollectionShare>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditCollectionShare extends BaseForm
{
    #[Form\Field]
    public string $type = '';
}
