<?php

namespace flusio\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Link extends \Minz\Model
{
    public const PROPERTIES = [
        'id' => [
            'type' => 'string',
            'required' => true,
        ],

        'created_at' => 'datetime',

        'title' => [
            'type' => 'string',
            'required' => true,
        ],

        'url' => [
            'type' => 'string',
            'required' => true,
            'validator' => '\flusio\models\Link::validateUrl',
        ],

        'is_public' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'in_news' => [
            'type' => 'boolean',
            'required' => true,
        ],

        'reading_time' => [
            'type' => 'integer',
            'required' => true,
        ],

        'fetched_at' => [
            'type' => 'datetime',
        ],

        'fetched_code' => [
            'type' => 'integer',
        ],

        'fetched_error' => [
            'type' => 'string',
        ],

        'user_id' => [
            'type' => 'string',
            'required' => true,
        ],
    ];

    /**
     * @param string $url
     * @param string $user_id
     * @param boolean|string $is_public
     *
     * @return \flusio\models\Link
     */
    public static function init($url, $user_id, $is_public)
    {
        $url = \SpiderBits\Url::sanitize($url);
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'title' => $url,
            'url' => $url,
            'is_public' => filter_var($is_public, FILTER_VALIDATE_BOOLEAN),
            'in_news' => false,
            'user_id' => $user_id,
            'reading_time' => 0,
            'fetched_code' => 0,
        ]);
    }

    /**
     * @param \flusio\models\NewsLink $news_link
     * @param string $user_id
     *
     * @return \flusio\models\Link
     */
    public static function initFromNews($news_link, $user_id)
    {
        return new self([
            'id' => bin2hex(random_bytes(16)),
            'title' => $news_link->title,
            'url' => $news_link->url,
            'is_public' => false,
            'in_news' => false,
            'reading_time' => $news_link->reading_time,
            'fetched_at' => \Minz\Time::now(),
            'fetched_code' => 200,
            'user_id' => $user_id,
        ]);
    }

    /**
     * Return the collections attached to the current link
     *
     * @return \flusio\models\Collection[]
     */
    public function collections()
    {
        $collection_dao = new dao\Collection();
        $collections = [];
        $db_collections = $collection_dao->listByLinkId($this->id);
        foreach ($db_collections as $db_collection) {
            $collections[] = new Collection($db_collection);
        }
        return $collections;
    }

    /**
     * Return the messages attached to the current link
     *
     * @return \flusio\models\Message[]
     */
    public function messages()
    {
        $message_dao = new dao\Message();
        $messages = [];
        $db_messages = $message_dao->listBy([
            'link_id' => $this->id,
        ]);
        foreach ($db_messages as $db_message) {
            $messages[] = new Message($db_message);
        }
        return $messages;
    }

    /**
     * @return string
     */
    public function host()
    {
        return \flusio\utils\Belt::host($this->url);
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

            if ($property === 'url' && $code === 'required') {
                $formatted_error = _('The link is required.');
            } elseif ($property === 'title' && $code === 'required') {
                $formatted_error = _('The title is required.');
            } else {
                $formatted_error = $error['description'];
            }

            $formatted_errors[$property] = $formatted_error;
        }

        return $formatted_errors;
    }

    /**
     * @param string $url
     * @return boolean
     */
    public static function validateUrl($url)
    {
        $validate = filter_var($url, FILTER_VALIDATE_URL) !== false;
        if (!$validate) {
            return _('The link is invalid.'); // @codeCoverageIgnore
        }

        $parsed_url = parse_url($url);
        if ($parsed_url['scheme'] !== 'http' && $parsed_url['scheme'] !== 'https') {
            return _('Link scheme must be either http or https.');
        }

        if (isset($parsed_url['pass'])) {
            return _('The link must not include a password as it’s sensitive data.'); // @codeCoverageIgnore
        }

        return true;
    }
}
