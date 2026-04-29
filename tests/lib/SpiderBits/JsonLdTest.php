<?php

namespace SpiderBits;

class JsonLdTest extends \PHPUnit\Framework\TestCase
{
    public function testDurationWithRootAttribute(): void
    {
        $json_ld_array = [
            '@context' => 'http://schema.org',
            '@type' => 'VideoObject',
            'name' => 'My video',
            'url' => 'https://videos.example.com/my-video',
            'duration' => 'PT2520',
        ];
        $json_ld = new JsonLd($json_ld_array);

        $duration = $json_ld->duration();

        $this->assertSame('PT2520', $duration);
    }

    public function testDurationInMainEntity(): void
    {
        $json_ld_array = [
            '@context' => 'http://schema.org',
            '@type' => 'RadioEpisode',
            'mainEntity' => [
                '@type' => 'AudioObject',
                'contentUrl' => 'https://radio.example.com/episode',
                'duration' => 'PT2520',
            ],
        ];

        $json_ld = new JsonLd($json_ld_array);

        $duration = $json_ld->duration();

        $this->assertSame('PT2520', $duration);
    }

    public function testDurationInGraphAttribute(): void
    {
        $json_ld_array = [
            '@context' => 'http://schema.org',
            '@graph' => [
                [
                    '@type' => 'VideoObject',
                    'name' => 'My video',
                    'url' => 'https://videos.example.com/my-video',
                    'duration' => 'PT2520',
                ],
            ],
        ];
        $json_ld = new JsonLd($json_ld_array);

        $duration = $json_ld->duration();

        $this->assertSame('PT2520', $duration);
    }
}
