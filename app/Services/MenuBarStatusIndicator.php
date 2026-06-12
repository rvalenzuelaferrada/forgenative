<?php

namespace App\Services;

use App\DeploymentHealth;
use Illuminate\Support\Facades\Storage;
use Native\Desktop\Facades\MenuBar;
use RuntimeException;

class MenuBarStatusIndicator
{
    private const PATH = 'preferences/menu-bar-status';

    public function current(): DeploymentHealth
    {
        if (! Storage::disk('local')->exists(self::PATH)) {
            return DeploymentHealth::Healthy;
        }

        return DeploymentHealth::tryFrom(
            trim((string) Storage::disk('local')->get(self::PATH)),
        ) ?? DeploymentHealth::Healthy;
    }

    public function update(DeploymentHealth $health): void
    {
        if (! Storage::disk('local')->put(self::PATH, $health->value)) {
            throw new RuntimeException('The menu bar status could not be stored.');
        }

        if (! $this->isRunningNatively()) {
            return;
        }

        MenuBar::icon($this->iconPath($health));
        MenuBar::tooltip($this->tooltip($health));
    }

    public function iconPath(DeploymentHealth $health): string
    {
        return public_path("menu-bar/status-{$health->value}.png");
    }

    public function tooltip(DeploymentHealth $health): string
    {
        return trans(
            "forge.menu_bar.{$health->value}",
            locale: app(LocalePreference::class)->get() ?? 'en',
        );
    }

    private function isRunningNatively(): bool
    {
        return filter_var(config('nativephp.running', false), FILTER_VALIDATE_BOOL);
    }
}
