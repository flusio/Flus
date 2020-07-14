<?php

namespace flusio\models;

/**
 * Represent a list containing a set of links.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Collection extends \Minz\Model
{
    public const VALID_TYPES = ['bookmarks', 'collection'];

    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'name' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Collection::validateName',
        ],

        'description' => [
            'type' => 'string',
        ],

        'type' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Collection::validateType',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],

        // This property is dynamic and not directly saved in DB. It may
        // generate errors if used inappropriately.
        'number_links' => [
            'type' => 'integer',
        ],
    ];

    /**
     * @param string $user_id
     * @param string $name
     * @param string $description
     *
     * @return \flusio\models\Collection
     */
    public static function init($user_id, $name, $description)
    {
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'name' => trim($name),
            'description' => trim($description),
            'type' => 'collection',
            'user_id' => $user_id,
        ]);
    }

    /**
     * @param string $user_id
     *
     * @return \flusio\models\Collection
     */
    public static function initBookmarks($user_id)
    {
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'name' => _('Bookmarks'),
            'type' => 'bookmarks',
            'user_id' => $user_id,
        ]);
    }

    /**
     * Return the list of links attached to this collection
     *
     * @return \flusio\models\Link[]
     */
    public function links()
    {
        $link_dao = new dao\Link();
        $db_links = $link_dao->listByCollectionId($this->id);
        $links = [];
        foreach ($db_links as $db_link) {
            $links[] = new Link($db_link);
        }
        return $links;
    }

    /**
     * @param string $type
     * @return boolean
     */
    public static function validateType($type)
    {
        return in_array($type, self::VALID_TYPES);
    }

    /**
     * @param string $name
     * @return boolean
     */
    public static function validateName($name)
    {
        return strlen($name) <= 100;
    }

    /**
     * Return a list of errors (if any). The array keys indicated the concerned
     * property.
     *
     * @return string[]
     */
    public function validate()
    {
        $formatted_errors = [];

        foreach (parent::validate() as $property => $error) {
            $code = $error['code'];

            if ($property === 'name' && $code === 'required') {
                $formatted_error = _('The name is required.');
            } elseif ($property === 'name') {
                $formatted_error = _('The name must be less than 100 characters.');
            } else {
                $formatted_error = $error['description']; // @codeCoverageIgnore
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
    }
}
