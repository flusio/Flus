<?php

namespace App\twig;

use App\auth;
use App\models;
use Twig\Attribute\AsTwigFunction;

class AuthExtension
{
    #[AsTwigFunction('can')]
    public static function can(?models\User $user, string $action, object $subject): bool
    {
        return auth\Access::can($user, $action, $subject);
    }
}
