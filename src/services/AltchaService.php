<?php

namespace App\services;

use AltchaOrg\Altcha;

class AltchaService
{
    private Altcha\Altcha $altcha;

    private Altcha\Algorithm\Pbkdf2 $pbkdf2;

    public function __construct()
    {
        $this->altcha = new Altcha\Altcha(
            hmacSignatureSecret: \App\Configuration::$secret_key,
        );
        $this->pbkdf2 = new Altcha\Algorithm\Pbkdf2();
    }

    public function buildChallenge(int $cost = 5000): Altcha\Challenge
    {
        $challenge_options = new Altcha\CreateChallengeOptions(
            algorithm: $this->pbkdf2,
            cost: $cost,
            counter: random_int(5000, 10000),
            expiresAt: \Minz\Time::fromNow(10, 'minutes'),
        );

        return $this->altcha->createChallenge($challenge_options);
    }

    public function solveChallenge(Altcha\Challenge $challenge): Altcha\Solution
    {
        $solution_options = new Altcha\SolveChallengeOptions(
            challenge: $challenge,
            algorithm: $this->pbkdf2,
        );

        $solution = $this->altcha->solveChallenge($solution_options);

        assert($solution !== null);

        return $solution;
    }

    public function buildPayload(Altcha\Challenge $challenge, Altcha\Solution $solution): Altcha\Payload
    {
        return new Altcha\Payload($challenge, $solution);
    }

    public function verifySolution(string $field): bool
    {
        list(
            $challenge_parameters,
            $challenge_signature,
            $solution_counter,
            $solution_derived_key,
        ) = $this->parseAltchaField($field);

        $challenge_parameters = Altcha\ChallengeParameters::fromArray($challenge_parameters);

        $challenge = new Altcha\Challenge(
            $challenge_parameters,
            $challenge_signature,
        );
        $solution = new Altcha\Solution(
            counter: $solution_counter,
            derivedKey: $solution_derived_key,
        );

        $payload = new Altcha\Payload($challenge, $solution);

        $solution_options = new Altcha\VerifySolutionOptions(
            payload: $payload,
            algorithm: $this->pbkdf2,
        );

        $result = $this->altcha->verifySolution($solution_options);

        return $result->verified;
    }

    /**
     * @return array{mixed[], string, int, string}
     */
    private function parseAltchaField(string $field): array
    {
        $decoded = base64_decode($field, strict: true);
        if ($decoded === false) {
            throw new \RuntimeException('Invalid base64 altcha value.');
        }

        $payload = json_decode($decoded, associative: true);
        if (!is_array($payload)) {
            throw new \RuntimeException('Invalid JSON in altcha value.');
        }

        $challenge = $payload['challenge'] ?? [];
        $solution = $payload['solution'] ?? [];

        if (!is_array($challenge) || !is_array($solution)) {
            throw new \RuntimeException('Invalid JSON value (challenge or solution) in altcha value.');
        }

        $challenge_parameters = $challenge['parameters'] ?? [];
        if (!is_array($challenge_parameters)) {
            $challenge_parameters = [];
        }

        $challenge_signature = $challenge['signature'] ?? '';
        if (!is_string($challenge_signature)) {
            $challenge_signature = '';
        }

        $solution_counter = $solution['counter'] ?? 0;
        $solution_counter = is_numeric($solution_counter) ? (int) $solution_counter : 0;

        $solution_derived_key = $solution['derivedKey'] ?? '';
        $solution_derived_key = is_string($solution_derived_key) ? $solution_derived_key : '';

        return [$challenge_parameters, $challenge_signature, $solution_counter, $solution_derived_key];
    }
}
