<?php

namespace App\utils;

/**
 * @author Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Notification
{
    private const SUCCESS_KEY = 'notification.success';
    private const ERROR_KEY = 'notification.error';

    public static function success(string $message): void
    {
        \Minz\Flash::set(self::SUCCESS_KEY, $message);
    }

    public static function error(string $message): void
    {
        \Minz\Flash::set(self::ERROR_KEY, $message);
    }

    public static function popSuccess(): string
    {
        $message = \Minz\Flash::pop(self::SUCCESS_KEY, '');

        if (!is_string($message)) {
            throw new \LogicException('Success notification must be a string');
        }

        return $message;
    }

    public static function popError(): string
    {
        $message = \Minz\Flash::pop(self::ERROR_KEY, '');

        if (!is_string($message)) {
            throw new \LogicException('Error notification must be a string');
        }

        return $message;
    }

    /**
     * @return array{
     *     success?: string,
     *     error?: string,
     * }
     */
    public static function popAll(): array
    {
        $notifications = [
            'success' => self::popSuccess(),
            'error' => self::popError(),
        ];

        $notifications = array_filter($notifications);

        return $notifications;
    }
}
