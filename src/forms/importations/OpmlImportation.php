<?php

namespace App\forms\importations;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Validable;
use Minz\Translatable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class OpmlImportation extends BaseForm
{
    use utils\Memoizer;

    public const OPML_TYPES = [
        'opml' => ['text/x-opml', 'text/x-opml+xml'],
        'xml' => ['application/xml', 'text/xml'],
    ];

    #[Form\Field]
    #[Validable\Presence(
        message: new Translatable('The file is required.'),
    )]
    #[Validable\File(
        types: self::OPML_TYPES,
        types_message: new Translatable('The file type must be one of the following: {types}.'),
        max_size_message: new Translatable('This file is too large (max allowed: {max_size}).'),
        message: new Translatable('This file cannot be uploaded (error {code}).'),
    )]
    public ?\Minz\File $opml = null;

    public function importation(): ?models\Importation
    {
        if (!$this->opml) {
            return null;
        }

        $user = $this->optionAs('user', models\User::class);

        $importations_filepath = \App\Configuration::$data_path . '/importations';
        if (!file_exists($importations_filepath)) {
            @mkdir($importations_filepath);
        }

        $opml_filepath = "{$importations_filepath}/opml_{$user->id}.xml";
        $is_moved = $this->opml->move($opml_filepath);

        if (!$is_moved) {
            return null;
        }

        return new models\Importation('opml', $user->id, [
            'opml_filepath' => $opml_filepath,
        ]);
    }

    public function currentImportation(): ?models\Importation
    {
        return $this->memoize('current_importation', function (): ?models\Importation {
            $user = $this->optionAs('user', models\User::class);
            return models\Importation::findOpmlByUser($user);
        });
    }

    public function acceptedMimetypes(): string
    {
        $accept = [];
        foreach (self::OPML_TYPES as $mimetypes) {
            $accept = array_merge($accept, $mimetypes);
        }
        return implode(', ', $accept);
    }

    #[Validable\Check]
    public function checkNoOngoingImportation(): void
    {
        $current_importation = $this->currentImportation();
        if ($current_importation) {
            $this->addError(
                '@base',
                'existing_importation',
                _('You already have an ongoing OPML importation.'),
            );
        }
    }
}
