<?php

namespace App\images;

class ImageTest extends \PHPUnit\Framework\TestCase
{
    public function testFromString(): void
    {
        $image_filepath = \App\Configuration::$app_path . '/public/static/og-card.png';
        $string_image = file_get_contents($image_filepath);
        assert($string_image !== false);

        $image = Image::fromString($string_image);

        $this->assertInstanceOf(Image::class, $image);
    }

    public function testFromStringFailsIfImageIsCorrupted(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Cannot create an image from the given string');

        $image_filepath = \App\Configuration::$app_path . '/tests/fixtures/images/corrupted.png';
        $string_image = file_get_contents($image_filepath);
        assert($string_image !== false);

        Image::fromString($string_image);
    }

    public function testFromStringFailsIfImageDimensionsAreTooLarge(): void
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('The image is too large (10000x10000px)');

        $image_filepath = \App\Configuration::$app_path . '/tests/fixtures/images/too-large.png';
        $string_image = file_get_contents($image_filepath);
        assert($string_image !== false);

        Image::fromString($string_image);
    }

    /**
     * @param array{int, int} $initial_size
     * @param array{int, int} $destination_size
     * @param array{
     *     'x': int,
     *     'y': int,
     *     'width': int,
     *     'height': int,
     * } $expected_rect
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('resizeProvider')]
    public function testResizeRectangle(array $initial_size, array $destination_size, array $expected_rect): void
    {
        $src_rect = Image::resizeRectangle($initial_size, $destination_size);

        $this->assertSame($expected_rect['x'], $src_rect['x']);
        $this->assertSame($expected_rect['y'], $src_rect['y']);
        $this->assertSame($expected_rect['width'], $src_rect['width']);
        $this->assertSame($expected_rect['height'], $src_rect['height']);
    }

    /**
     * @return array<array{
     *     array{int, int},
     *     array{int, int},
     *     array{
     *         'x': int,
     *         'y': int,
     *         'width': int,
     *         'height': int,
     *     },
     * }>
     */
    public static function resizeProvider(): array
    {
        return [
            [
                [800, 600],
                [300, 150],
                [
                    'x' => 0,
                    'y' => 100,
                    'width' => 800,
                    'height' => 400,
                ],
            ],
            [
                [800, 600],
                [1000, 200],
                [
                    'x' => 0,
                    'y' => 220,
                    'width' => 800,
                    'height' => 160,
                ],
            ],
            [
                [300, 150],
                [300, 150],
                [
                    'x' => 0,
                    'y' => 0,
                    'width' => 300,
                    'height' => 150,
                ],
            ],
            [
                [1500, 600],
                [300, 150],
                [
                    'x' => 150,
                    'y' => 0,
                    'width' => 1200,
                    'height' => 600,
                ],
            ],
        ];
    }
}
