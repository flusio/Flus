<?php

namespace App\services;

use App\models;

/**
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class Bileto
{
    private string $url;
    private string $api_token;

    public function __construct()
    {
        $this->url = \App\Configuration::$application['bileto_url'];
        $this->api_token = \App\Configuration::$application['bileto_api_token'];
    }

    public function isEnabled(): bool
    {
        return $this->url && $this->api_token;
    }

    public function sendMessage(models\User $user, string $subject, string $message): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        try {
            $endpoint = "{$this->url}/api/tickets";
            $http = new \SpiderBits\Http();

            $response = $http->post($endpoint, [
                'from' => $user->email,
                'title' => $subject,
                'content' => $message,
            ], [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer {$this->api_token}",
                ],
            ]);

            return $response->success;
        } catch (\SpiderBits\HttpError $e) {
            \Minz\Log::error($e->getMessage());

            return false;
        }
    }
}
