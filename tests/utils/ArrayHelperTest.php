<?php

namespace App\utils;

class ArrayHelperTest extends \PHPUnit\Framework\TestCase
{
    public function testFindReturnsTheFirstMatchingElement(): void
    {
        $array = [1, 2, 3, 4];

        $result = ArrayHelper::find($array, function (int $value): bool {
            return $value % 2 === 0;
        });

        $this->assertSame(2, $result);
    }

    public function testFindPassesTheKeyToTheCallback(): void
    {
        $array = ['foo' => 1, 'bar' => 2];

        $result = ArrayHelper::find($array, function (int $value, mixed $key): bool {
            return $key === 'bar';
        });

        $this->assertSame(2, $result);
    }

    public function testFindReturnsNullIfNoElementMatches(): void
    {
        $threshold = new \DateTimeImmutable('2026-01-01');
        $array = [
            new \DateTimeImmutable('2025-03-01'),
            new \DateTimeImmutable('2025-06-15'),
        ];

        $result = ArrayHelper::find($array, function (\DateTimeImmutable $date) use ($threshold): bool {
            return $date > $threshold;
        });

        $this->assertNull($result);
    }

    public function testAnyReturnsTrueIfAtLeastOneElementMatches(): void
    {
        $array = [1, 2, 3];

        $result = ArrayHelper::any($array, function (int $value): bool {
            return $value % 2 === 0;
        });

        $this->assertTrue($result);
    }

    public function testAnyPassesTheKeyToTheCallback(): void
    {
        $array = ['foo' => 1, 'bar' => 2];

        $result = ArrayHelper::any($array, function (int $value, mixed $key): bool {
            return $key === 'bar';
        });

        $this->assertTrue($result);
    }

    public function testAnyReturnsFalseIfNoElementMatches(): void
    {
        $threshold = new \DateTimeImmutable('2026-01-01');
        $array = [
            new \DateTimeImmutable('2025-03-01'),
            new \DateTimeImmutable('2025-06-15'),
        ];

        $result = ArrayHelper::any($array, function (\DateTimeImmutable $date) use ($threshold): bool {
            return $date > $threshold;
        });

        $this->assertFalse($result);
    }

    public function testTrimTrimsEachString(): void
    {
        $strings = ['  foo', 'bar  ', "\tbaz\n"];

        $result = ArrayHelper::trim($strings);

        $this->assertSame(['foo', 'bar', 'baz'], $result);
    }

    public function testTrimKeepsTheKeys(): void
    {
        $strings = ['foo' => ' spam ', 'bar' => ' egg '];

        $result = ArrayHelper::trim($strings);

        $this->assertSame(['foo' => 'spam', 'bar' => 'egg'], $result);
    }
}
