<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkAreaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $workAreas = $device->company
            ->workAreas()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'work_areas' => $workAreas->map(function ($workArea) {
                return [
                    'id' => $workArea->id,
                    'company_id' => $workArea->company_id,
                    'name' => $workArea->name,
                    'latitude' => (float) $workArea->latitude,
                    'longitude' => (float) $workArea->longitude,
                    'radius_meters' => $workArea->radius_meters,
                    'updated_at' => $workArea->updated_at,
                ];
            }),
        ]);
    }
}
