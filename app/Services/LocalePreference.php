<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use RuntimeException;

class LocalePreference
{
    private const PATH = 'preferences/locale';

    /**
     * @var array<int, string>
     */
    private const SUPPORTED_LOCALES = ['en', 'es'];

    public function get(): ?string
    {
        if (! Storage::disk('local')->exists(self::PATH)) {
            return null;
        }

        $locale = trim((string) Storage::disk('local')->get(self::PATH));

        return in_array($locale, self::SUPPORTED_LOCALES, true)
            ? $locale
            : null;
    }

    public function set(string $locale): void
    {
        if (! in_array($locale, self::SUPPORTED_LOCALES, true)) {
            throw new RuntimeException('Unsupported locale.');
        }

        if (! Storage::disk('local')->put(self::PATH, $locale)) {
            throw new RuntimeException('The locale preference could not be stored.');
        }
    }
}
