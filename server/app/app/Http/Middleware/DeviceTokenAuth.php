<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Hiányzó eszköz token.',
            ], 401);
        }

        $device = Device::query()
            ->where('api_token', $token)
            ->where('active', true)
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Érvénytelen vagy inaktív eszköz token.',
            ], 401);
        }

        $device->last_seen_at = now();
        $device->save();

        $request->attributes->set('device', $device);

        return $next($request);
    }
}
