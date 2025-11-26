<?php

namespace App\twig;

use Twig\Attribute\AsTwigFunction;

class FormsExtension
{
    /**
     * Return a CSRF token from a form.
     *
     * A string can be passed. A \App\forms\{$form} form will be instantiated then.
     * The instantiated form must be an instance of \App\forms\BaseForm.
     *
     * @throws \InvalidArgumentException
     *     Raised if the form instantiated from the string isn't a \App\forms\BaseForm.
     *
     * @param \App\forms\BaseForm|string $form
     */
    #[AsTwigFunction('csrf_token')]
    public static function csrfToken(mixed $form): string
    {
        if (is_string($form)) {
            $form_class = "\\App\\forms\\{$form}";
            $form = new $form_class();

            if (!($form instanceof \App\forms\BaseForm)) {
                throw new \InvalidArgumentException("{$form_class} is not an instance of \\App\\forms\\BaseForm");
            }
        }

        return $form->csrfToken();
    }
}
