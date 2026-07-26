<?php

use App\Modules\Administration\Models\LogActivity;
use Illuminate\Support\Facades\Auth;
use Jenssegers\Agent\Agent;

if (! function_exists('logActivity')) {

    /**
     * Log system activity
     *
     * @param string|null $action
     * @param mixed|null $model
     * @param array|null $data
     * @return void
     */
    function logActivity(?string $action = null, ?array $data = null, $model = null): void
    {
        try {
            $request = request();
            $agent = new Agent();

            LogActivity::create([
                'user_id' => Auth::id(),
                'ip'      => $request->ip(),
                'machine' => $agent->device(),
                'system'  => $agent->platform(),
                'browser' => $agent->browser(),
                'model'   => $model ? get_class($model) : null,
                'action'  => $action,
                'data'    => $data,
            ]);
        } catch (\Throwable $e) {
            // Fail silently — logging must never break the app
            logger()->error('Erreur de log activity', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
