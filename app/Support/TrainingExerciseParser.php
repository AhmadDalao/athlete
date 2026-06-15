<?php

namespace App\Support;

class TrainingExerciseParser
{
    /**
     * @return list<array{
     *     name:string,
     *     prescription:?string,
     *     sets:?int,
     *     reps:?string,
     *     load:?string,
     *     rest_seconds:?int,
     *     rest_label:?string,
     *     target:?string,
     *     note:?string
     * }>
     */
    public function parse(?string $input): array
    {
        if (! is_string($input) || trim($input) === '') {
            return [];
        }

        return collect(preg_split('/\r\n|\r|\n/', trim($input)) ?: [])
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->map(fn (string $line): array => $this->parseLine($line))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     name:string,
     *     prescription:?string,
     *     sets:?int,
     *     reps:?string,
     *     load:?string,
     *     rest_seconds:?int,
     *     rest_label:?string,
     *     target:?string,
     *     note:?string
     * }
     */
    private function parseLine(string $line): array
    {
        $parts = array_map('trim', explode('|', $line));
        $name = $this->cleanText($parts[0] ?? null) ?? $line;

        if (count($parts) >= 4) {
            $sets = $this->parsePositiveInt($parts[1] ?? null);
            $reps = $this->cleanText($parts[2] ?? null);
            $load = $this->cleanText($parts[3] ?? null);
            $restLabel = $this->cleanText($parts[4] ?? null);
            $target = $this->cleanText($parts[5] ?? null);
            $note = $this->cleanText($parts[6] ?? null);

            return [
                'name' => $name,
                'prescription' => $this->buildPrescription($sets, $reps, $load, $restLabel, $target),
                'sets' => $sets,
                'reps' => $reps,
                'load' => $load,
                'rest_seconds' => $this->parseDurationSeconds($restLabel),
                'rest_label' => $restLabel,
                'target' => $target,
                'note' => $note,
            ];
        }

        $prescription = $this->cleanText($parts[1] ?? null);
        $parsedLegacy = $this->parseLegacyPrescription($prescription);

        return [
            'name' => $name,
            'prescription' => $prescription,
            'sets' => $parsedLegacy['sets'],
            'reps' => $parsedLegacy['reps'],
            'load' => $parsedLegacy['load'],
            'rest_seconds' => $parsedLegacy['rest_seconds'],
            'rest_label' => $parsedLegacy['rest_label'],
            'target' => $parsedLegacy['target'],
            'note' => $this->cleanText($parts[2] ?? null),
        ];
    }

    /**
     * @return array{sets:?int,reps:?string,load:?string,rest_seconds:?int,rest_label:?string,target:?string}
     */
    private function parseLegacyPrescription(?string $prescription): array
    {
        if (! $prescription) {
            return [
                'sets' => null,
                'reps' => null,
                'load' => null,
                'rest_seconds' => null,
                'rest_label' => null,
                'target' => null,
            ];
        }

        $sets = null;
        $reps = null;
        $load = null;
        $target = null;
        $restLabel = null;

        if (preg_match('/^(?<sets>\d+)\s*x\s*(?<remainder>.+)$/i', $prescription, $matches) === 1) {
            $sets = (int) $matches['sets'];
            $remainder = trim($matches['remainder']);

            [$mainPart, $restCandidate] = array_pad(preg_split('/\s+(?:rest|recover(?:y)?)\s*[:\-]?\s*/i', $remainder, 2) ?: [], 2, null);
            $restLabel = $this->cleanText($restCandidate);

            if (is_string($mainPart) && str_contains($mainPart, '@')) {
                [$repsPart, $targetPart] = array_pad(explode('@', $mainPart, 2), 2, null);
                $reps = $this->cleanText($repsPart);
                $candidate = $this->cleanText($targetPart);

                if ($candidate && $this->looksLikeLoad($candidate)) {
                    $load = $candidate;
                } else {
                    $target = $candidate;
                }
            } else {
                $reps = $this->cleanText($mainPart);
            }
        }

        return [
            'sets' => $sets,
            'reps' => $reps,
            'load' => $load,
            'rest_seconds' => $this->parseDurationSeconds($restLabel),
            'rest_label' => $restLabel,
            'target' => $target,
        ];
    }

    private function buildPrescription(?int $sets, ?string $reps, ?string $load, ?string $restLabel, ?string $target): ?string
    {
        $parts = [];

        if ($sets !== null || $reps !== null) {
            $parts[] = trim(collect([
                $sets !== null ? (string) $sets : null,
                $sets !== null && $reps !== null ? 'x' : null,
                $reps,
            ])->filter()->implode(' '));
        }

        if ($load) {
            $parts[] = "load {$load}";
        }

        if ($restLabel) {
            $parts[] = "rest {$restLabel}";
        }

        if ($target) {
            $parts[] = $target;
        }

        $summary = collect($parts)
            ->filter(fn (?string $part) => is_string($part) && trim($part) !== '')
            ->implode(' · ');

        return $summary !== '' ? $summary : null;
    }

    private function parsePositiveInt(?string $value): ?int
    {
        if (! is_string($value) || trim($value) === '' || ! ctype_digit(trim($value))) {
            return null;
        }

        $parsed = (int) trim($value);

        return $parsed > 0 ? $parsed : null;
    }

    private function parseDurationSeconds(?string $value): ?int
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $normalized = strtolower(trim($value));

        if (preg_match('/^(?<minutes>\d+)\s*(?:m|min|mins|minute|minutes)$/', $normalized, $matches) === 1) {
            return ((int) $matches['minutes']) * 60;
        }

        if (preg_match('/^(?<seconds>\d+)\s*(?:s|sec|secs|second|seconds)$/', $normalized, $matches) === 1) {
            return (int) $matches['seconds'];
        }

        if (preg_match('/^(?<minutes>\d+):(?<seconds>\d{2})$/', $normalized, $matches) === 1) {
            return (((int) $matches['minutes']) * 60) + (int) $matches['seconds'];
        }

        return ctype_digit($normalized) ? (int) $normalized : null;
    }

    private function cleanText(?string $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function looksLikeLoad(string $value): bool
    {
        $normalized = strtolower($value);

        return str_contains($normalized, 'kg')
            || str_contains($normalized, 'lb')
            || str_contains($normalized, '%')
            || str_contains($normalized, 'bodyweight');
    }
}
