<?php

namespace App\cache;

/**
 * Represents an item saved in a cache pool.
 *
 * @author  Marien Fressinaud <dev@marienfressinaud.fr>
 * @license http://www.gnu.org/licenses/agpl-3.0.en.html AGPL
 */
class CacheItem
{
    private ?\DateTimeInterface $expiration = null;

    public function __construct(
        private string $key,
        private mixed $value,
        private bool $is_hit = false,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getExpiration(): ?\DateTimeInterface
    {
        return $this->expiration;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->is_hit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function expiresAt(?\DateTimeInterface $expiration): static
    {
        $this->expiration = $expiration;

        return $this;
    }

    public function hasExpired(): bool
    {
        return $this->expiration && \Minz\Time::now() >= $this->expiration;
    }
}
