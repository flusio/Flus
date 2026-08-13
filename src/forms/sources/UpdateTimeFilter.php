<?php

namespace App\forms\sources;

use App\models;
use Minz\Form;
use Minz\Translatable;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class UpdateTimeFilter extends BulkSelection
{
    #[Form\Field]
    #[Validable\Presence(
        message: new Translatable('The filter is required.'),
    )]
    #[Validable\Inclusion(
        in: models\FollowedCollection::VALID_TIME_FILTERS,
        message: new Translatable('The filter is invalid.'),
    )]
    public string $time_filter = '';
}
