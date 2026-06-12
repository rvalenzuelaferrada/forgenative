<?php

namespace App\Jobs;

use App\Services\ForgeDeploymentMonitor;
use App\Services\MenuBarStatusIndicator;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshMenuBarStatus implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 55;

    public int $uniqueFor = 30;

    public function handle(
        ForgeDeploymentMonitor $monitor,
        MenuBarStatusIndicator $indicator,
    ): void {
        $health = $monitor->scan();

        if ($health !== null) {
            $indicator->update($health);
        }
    }

    public function uniqueId(): string
    {
        return 'forge-deployment-menu-bar-status';
    }
}
