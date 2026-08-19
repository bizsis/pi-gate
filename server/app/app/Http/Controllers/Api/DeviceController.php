<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DeviceController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => [
                'required',
                'integer',
                'exists:companies,id',
            ],
            'device_uid' => [
                'required',
                'string',
                'max:255',
            ],
            'name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'platform' => [
                'nullable',
                'string',
                'max:50',
            ],
            'app_version' => [
                'nullable',
                'string',
                'max:50',
            ],
        ]);

        $company = Company::query()
            ->where('id', $validated['company_id'])
            ->where('active', true)
            ->first();

        if (!$company) {
            return response()->json([
                'success' => false,
                'message' => 'A cég nem található vagy inaktív.',
            ], 422);
        }

        $device = Device::query()
            ->where('device_uid', $validated['device_uid'])
            ->first();

        if ($device && $device->company_id !== $company->id) {
            return response()->json([
                'success' => false,
                'message' => 'Ez az eszköz már egy másik céghez van rendelve.',
            ], 409);
        }

        if (!$device) {
            $device = new Device();

            $device->company_id = $company->id;
            $device->device_uid = $validated['device_uid'];
            $device->api_token = Str::random(64);
        }

        if (!$device->api_token) {
            $device->api_token = Str::random(64);
        }

        $device->name =
            $validated['name'] ?? $device->name;

        $device->platform =
            $validated['platform'] ?? 'android';

        $device->app_version =
            $validated['app_version'] ?? $device->app_version;

        $device->last_seen_at = now();
        $device->active = true;

        $device->save();

        return response()->json([
            'success' => true,

            'device' => [
                'id' => $device->id,
                'device_uid' => $device->device_uid,
                'name' => $device->name,
                'company_id' => $device->company_id,
                'platform' => $device->platform,
                'app_version' => $device->app_version,
                'active' => $device->active,
                'last_seen_at' => $device->last_seen_at,
            ],

            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'short_name' => $company->short_name,
                'tax_number' => $company->tax_number,
                'registration_number' => $company->registration_number,
                'email' => $company->email,
                'phone' => $company->phone,
                'address' => $company->address,
                'active' => $company->active,
                'updated_at' => $company->updated_at,
            ],

            'token' => $device->api_token,
        ]);
    }
}
