<?php

namespace App\forms\collections;

use App\forms\BaseForm;
use App\models;
use App\utils;
use Minz\Form;
use Minz\Validable;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class EditCollectionGroup extends BaseForm
{
    use utils\Memoizer;

    #[Form\Field(transform: 'trim')]
    public string $name = '';

    public int $group_name_max_length = models\Group::NAME_MAX_LENGTH;

    /**
     * @return models\Group[]
     */
    public function groups(): array
    {
        return $this->memoize('groups', function (): array {
            $user = $this->user();
            $groups = models\Group::listBy([
                'user_id' => $user->id,
            ]);
            return utils\Sorter::localeSort($groups, 'name');
        });
    }

    /**
     * Find a group by its name from the groups values.
     */
    public function findGroup(string $group_name): ?models\Group
    {
        foreach ($this->groups() as $group) {
            if ($group->name === $group_name) {
                return $group;
            }
        }

        return null;
    }

    public function isGroupSelected(models\Group $group): bool
    {
        return $group->name === $this->name;
    }

    public function group(): ?models\Group
    {
        if (!$this->name) {
            return null;
        }

        $group = $this->findGroup($this->name);

        if ($group) {
            return $group;
        }

        $user = $this->user();
        return new models\Group($user->id, $this->name);
    }

    public function user(): models\User
    {
        $user = $this->options->get('user');

        if (!($user instanceof models\User)) {
            throw new \LogicException('User must be passed as an option of the form.');
        }

        return $user;
    }

    #[Validable\Check]
    public function checkGroupValidate(): void
    {
        $group = $this->group();

        if (!$group) {
            return;
        }

        if ($group->validate()) {
            return;
        }

        $group_errors = $group->errors(format: false);

        foreach ($group_errors as $field_name => $field_errors) {
            foreach ($field_errors as $field_error) {
                $this->addError($field_name, $field_error[0], $field_error[1]);
            }
        }
    }
}
