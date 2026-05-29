<?php

namespace App\forms\traits;

use App\services;
use Minz\Form;
use Minz\Validable;

trait Altcha
{
    #[Form\Field(bind: false)]
    public string $altcha = '';

    #[Validable\Check]
    public function checkAltcha(): void
    {
        if (!$this->mustCheckAltcha()) {
            return;
        }

        if (!$this->altcha) {
            $this->addError('@base', 'altcha_missing', $this->altchaMissingErrorMessage());
            return;
        }

        try {
            $altcha_service = new services\AltchaService();
            $verified = $altcha_service->verifySolution($this->altcha);
        } catch (\Exception $e) {
            $verified = false;
        }

        if (!$verified) {
            $this->addError('@base', 'altcha_invalid', $this->altchaInvalidErrorMessage());
            return;
        }
    }

    public function mustCheckAltcha(): bool
    {
        return true;
    }

    public function altchaMissingErrorMessage(): string
    {
        return _('The captcha has not been completed, please check “I’m not a robot”.');
    }

    public function altchaInvalidErrorMessage(): string
    {
        return _('The captcha is invalid, please try submitting the form again.');
    }
}
