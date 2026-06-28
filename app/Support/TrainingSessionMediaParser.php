<?php

namespace App\Support;

class TrainingSessionMediaParser
{
    /**
     * @return list<array{type:string,url:string,title:string|null}>
     */
    public function imageItems(?string $input): array
    {
        return collect($this->lines($input))
            ->filter(fn (string $url): bool => $this->isValidUrl($url))
            ->map(fn (string $url): array => [
                'type' => 'image',
                'url' => $url,
                'title' => null,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    public function invalidUrls(?string $input): array
    {
        return collect($this->lines($input))
            ->reject(fn (string $url): bool => $this->isValidUrl($url))
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function lines(?string $input): array
    {
        return collect(preg_split('/[\r\n,]+/', (string) $input) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();
    }

    private function isValidUrl(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }
}
