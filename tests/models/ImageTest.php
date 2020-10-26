<?php

namespace flusio\models;

class ImageTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider resizeProvider
     */
    public function testResizeRectangle($initial_size, $destination_size, $expected_rect)
    {
        $src_rect = Image::resizeRectangle($initial_size, $destination_size);

        $this->assertSame($expected_rect['x'], $src_rect['x']);
        $this->assertSame($expected_rect['y'], $src_rect['y']);
        $this->assertSame($expected_rect['width'], $src_rect['width']);
        $this->assertSame($expected_rect['height'], $src_rect['height']);
    }

    public function resizeProvider()
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
