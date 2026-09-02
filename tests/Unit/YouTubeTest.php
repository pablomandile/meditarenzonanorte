<?php

namespace Tests\Unit;

use App\Support\YouTube;
use PHPUnit\Framework\TestCase;

class YouTubeTest extends TestCase
{
    public function test_it_extracts_the_video_id_from_every_way_a_link_gets_pasted(): void
    {
        $id = 'dQw4w9WgXcQ';

        $cases = [
            'https://www.youtube.com/watch?v='.$id => $id,
            'https://youtube.com/watch?v='.$id => $id,
            'https://www.youtube.com/watch?v='.$id.'&t=42s&list=PLxyz' => $id,
            'https://www.youtube.com/watch?list=PLxyz&v='.$id => $id,
            'https://youtu.be/'.$id.'?si=abc123' => $id,
            'https://www.youtube.com/embed/'.$id => $id,
            'https://www.youtube.com/shorts/'.$id => $id,
            'https://www.youtube.com/live/'.$id => $id,
            $id => $id,
            '  https://youtu.be/'.$id.'  ' => $id,
            '' => null,
            'https://vimeo.com/12345' => null,
            'mirá este video' => null,
        ];

        foreach ($cases as $url => $expected) {
            $this->assertSame($expected, YouTube::id($url), $url);
        }

        $this->assertNull(YouTube::id(null));
    }

    public function test_it_builds_a_nocookie_embed_url(): void
    {
        $this->assertSame(
            'https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ',
            YouTube::embedUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ'),
        );

        $this->assertNull(YouTube::embedUrl('https://vimeo.com/12345'));
    }

    public function test_it_builds_a_thumbnail_url(): void
    {
        $this->assertSame(
            'https://i.ytimg.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            YouTube::thumbnailUrl('https://youtu.be/dQw4w9WgXcQ'),
        );
    }
}
