<?php

namespace App\forms;

use App\auth;
use Minz\Form;

/**
 * @phpstan-import-type ValidableError from \Minz\Validable
 *
 * @template T of object = \stdClass
 *
 * @phpstan-extends Form<T>
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class BaseForm extends Form
{
    use Form\Csrf {
        csrfSessionId as protected _csrfSessionId;
    }

    public function csrfErrorMessage(): string
    {
        return _('A security verification failed: you should retry to submit the form.');
    }

    public function csrfSessionId(): string
    {
        if (auth\CurrentUser::isLoggedIn()) {
            $session = auth\CurrentUser::session();
            return $session->id;
        } else {
            return $this->_csrfSessionId();
        }
    }

    /**
     * Return an option value, making sure it is returned as an object of the
     * expected class.
     *
     * @template TOption of object
     *
     * @param class-string<TOption> $expected_class
     *
     * @return TOption
     */
    public function optionAs(string $name, string $expected_class): mixed
    {
        $value = $this->options->get($name);

        if (!($value instanceof $expected_class)) {
            throw new \LogicException(
                "Option {$name} must be passed as an {$expected_class} option of the form."
            );
        }

        return $value;
    }

    /**
     * Return the mimetypes accepted by the given field, formatted for the
     * HTML `accept` attribute.
     *
     * The field must declare a checks\Image attribute.
     */
    public function imageAcceptedMimetypes(string $field): string
    {
        $property = new \ReflectionProperty($this, $field);
        $attributes = $property->getAttributes(checks\Image::class);

        if (!$attributes) {
            throw new \LogicException("Field {$field} doesn’t declare a checks\\Image attribute.");
        }

        return $attributes[0]->newInstance()->acceptedMimetypes();
    }

    /**
     * Copy the errors of a model which is not bound to the form.
     *
     * The errors are attached to the field of the same name, unless a field is
     * given: they are then all attached to it.
     *
     * @param array<string, ValidableError[]> $errors
     */
    public function addErrors(array $errors, ?string $field = null): void
    {
        foreach ($errors as $field_name => $field_errors) {
            foreach ($field_errors as $field_error) {
                $this->addError($field ?? $field_name, $field_error[0], $field_error[1]);
            }
        }
    }
}
