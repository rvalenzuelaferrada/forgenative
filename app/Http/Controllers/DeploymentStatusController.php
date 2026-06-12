<?php

namespace App\Http\Controllers;

use App\Services\ForgeDeploymentMonitor;
use App\Services\MenuBarStatusIndicator;
use Illuminate\Http\RedirectResponse;

class DeploymentStatusController extends Controller
{
    public function __invoke(
        ForgeDeploymentMonitor $monitor,
        MenuBarStatusIndicator $indicator,
    ): RedirectResponse {
        $health = $monitor->scan();

        if ($health !== null) {
            $indicator->update($health);
        }

        return back()->with('status', trans('forge.status.deployments_refreshed'));
    }
}
