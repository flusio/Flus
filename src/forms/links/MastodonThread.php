<?php

namespace App\forms\links;

use App\forms\BaseForm;
use Minz\Form;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class MastodonThread extends BaseForm
{
    /** @var string[] */
    #[Form\Field]
    public array $contents = [];

    public function defaultContent(): string
    {
        return $this->options->getString('default_content', '');
    }

    public function maxCharacters(): int
    {
        return $this->options->getInteger('max_chars', 500);
    }
}
