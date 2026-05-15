<?php

namespace App\models;

use App\utils;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Origin
{
    public readonly string $value;

    public readonly User|Link|Collection|null $model;

    public readonly string $label;

    public readonly string $url;

    public function __construct(string $value)
    {
        $this->value = $value;
        $this->model = $this->modelFromValue();
        $this->label = $this->labelFromValue();
        $this->url = $this->urlFromValue();
    }

    /**
     * Return the model (User or Collection) matching with the value if any.
     */
    private function modelFromValue(): User|Link|Collection|null
    {
        list($origin_type, $origin_id) = utils\OriginHelper::extractFromPath($this->value);

        if ($origin_type === 'user' && $origin_id) {
            return User::find($origin_id);
        } elseif ($origin_type === 'link' && $origin_id) {
            return Link::find($origin_id);
        } elseif ($origin_type === 'collection' && $origin_id) {
            return Collection::find($origin_id);
        } else {
            return null;
        }
    }

    /**
     * Return the label of the origin.
     *
     * The label is identical to the value, except if the value is an URL:
     *
     * - if the URL corresponds to a model or a user, the corresponding name is
     *   returned;
     * - otherwise, the host of the URL is returned.
     */
    private function labelFromValue(): string
    {
        if (!\SpiderBits\Url::isValid($this->value)) {
            return $this->value;
        }

        if ($this->model instanceof User) {
            return $this->model->username;
        } elseif ($this->model instanceof Link) {
            return $this->model->title;
        } elseif ($this->model instanceof Collection) {
            return $this->model->name();
        } else {
            return utils\Belt::host($this->value);
        }
    }

    /**
     * Return the value if the value is a valid URL.
     */
    private function urlFromValue(): string
    {
        if (!\SpiderBits\Url::isValid($this->value)) {
            return '';
        }

        if ($this->model instanceof User) {
            return \Minz\Url::absoluteFor('profile', ['id' => $this->model->id]);
        } elseif ($this->model instanceof Link) {
            return \Minz\Url::absoluteFor('link', ['id' => $this->model->id]);
        } elseif ($this->model instanceof Collection) {
            return \Minz\Url::absoluteFor('collection', ['id' => $this->model->id]);
        } else {
            return $this->value;
        }
    }
}
