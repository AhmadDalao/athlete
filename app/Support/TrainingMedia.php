<?php

namespace App\Support;

final class TrainingMedia
{
    /**
     * @return array{type: string, url: ?string, embedUrl: ?string}
     */
    public static function fromUrl(?string $url): array
    {
        if (! filled($url)) {
            return ['type' => 'none', 'url' => null, 'embedUrl' => null];
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $path = (string) parse_url($url, PHP_URL_PATH);
        $lowerPath = strtolower($path);

        if (preg_match('/\.(jpg|jpeg|png|gif|webp|avif)$/', $lowerPath)) {
            return ['type' => 'image', 'url' => $url, 'embedUrl' => null];
        }

        if (preg_match('/\.(mp4|webm|mov|m4v)$/', $lowerPath)) {
            return ['type' => 'video', 'url' => $url, 'embedUrl' => null];
        }

        if (str_contains($host, 'youtube.com') || str_contains($host, 'youtu.be')) {
            $videoId = self::youtubeVideoId($url, $host, $path);

            return [
                'type' => $videoId ? 'embed' : 'link',
                'url' => $url,
                'embedUrl' => $videoId ? 'https://www.youtube.com/embed/'.$videoId : null,
            ];
        }

        if (str_contains($host, 'vimeo.com')) {
            $videoId = self::vimeoVideoId($path);

            return [
                'type' => $videoId ? 'embed' : 'link',
                'url' => $url,
                'embedUrl' => $videoId ? 'https://player.vimeo.com/video/'.$videoId : null,
            ];
        }

        return ['type' => 'link', 'url' => $url, 'embedUrl' => null];
    }

    private static function youtubeVideoId(string $url, string $host, string $path): ?string
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        if (str_contains($host, 'youtu.be')) {
            return self::cleanVideoId(trim($path, '/'));
        }

        if (filled($query['v'] ?? null)) {
            return self::cleanVideoId((string) $query['v']);
        }

        if (preg_match('#/(?:embed|shorts)/([^/]+)#', $path, $matches)) {
            return self::cleanVideoId($matches[1]);
        }

        return null;
    }

    private static function vimeoVideoId(string $path): ?string
    {
        if (preg_match('#/(\d+)(?:/|$)#', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private static function cleanVideoId(string $videoId): ?string
    {
        $videoId = preg_replace('/[^A-Za-z0-9_-]/', '', $videoId) ?: '';

        return $videoId !== '' ? $videoId : null;
    }
}
