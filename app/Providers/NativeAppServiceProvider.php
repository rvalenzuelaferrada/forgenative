<?php

namespace App\Providers;

use App\Jobs\RefreshMenuBarStatus;
use App\Services\MenuBarStatusIndicator;
use Native\Desktop\Contracts\ProvidesPhpIni;
use Native\Desktop\Facades\Menu;
use Native\Desktop\Facades\MenuBar;

class NativeAppServiceProvider implements ProvidesPhpIni
{
    /**
     * Executed once the native application has been booted.
     * Use this method to open windows, register global shortcuts, etc.
     */
    public function boot(): void
    {
        $indicator = app(MenuBarStatusIndicator::class);
        $health = $indicator->current();

        MenuBar::create()
            ->route('sites.index')
            ->icon($indicator->iconPath($health))
            ->label('ForgeNative')
            ->tooltip($indicator->tooltip($health))
            ->width(420)
            ->height(640)
            ->resizable(false)
            ->backgroundColor('#09090b')
            ->showDockIcon(false)
            ->withContextMenu(
                Menu::make(
                    Menu::label('ForgeNative'),
                    Menu::separator(),
                    Menu::quit(),
                ),
            );

        RefreshMenuBarStatus::dispatch();
    }

    /**
     * Return an array of php.ini directives to be set.
     *
     * @return array<string, string>
     */
    public function phpIni(): array
    {
        return [
        ];
    }
}
