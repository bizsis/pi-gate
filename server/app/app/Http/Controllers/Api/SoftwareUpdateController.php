<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoftwareUpdate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class SoftwareUpdateController extends Controller
{
    public function current(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'version_code' => ['nullable', 'integer', 'min:0'],
        ]);

        $currentVersionCode = (int) ($validated['version_code'] ?? 0);

        $update = SoftwareUpdate::query()
            ->where('active', true)
            ->orderByDesc('version_code')
            ->first();

        if (!$update || $update->version_code <= $currentVersionCode) {
            return response()->json([
                'success' => true,
                'update_available' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'update_available' => true,
            'update' => [
                'version_code' => $update->version_code,
                'version_name' => $update->version_name,
                'download_url' => route('api.software-update.download'),
                'sha256' => $update->sha256,
                'file_size' => $update->file_size,
                'mandatory' => $update->mandatory,
                'notes' => $update->notes,
            ],
        ]);
    }

    public function download(): BinaryFileResponse|JsonResponse
    {
        $update = SoftwareUpdate::query()
            ->where('active', true)
            ->orderByDesc('version_code')
            ->first();

        if (!$update) {
            return response()->json([
                'success' => false,
                'message' => 'Nincs aktív szoftverfrissítés.',
            ], 404);
        }

        $path = storage_path('app/' . $update->apk_path);

        if (!is_file($path)) {
            return response()->json([
                'success' => false,
                'message' => 'A frissítő APK nem található.',
            ], 404);
        }

        return response()
            ->download($path, 'pi-gate-' . $update->version_name . '.apk', [
                'Content-Type' => 'application/vnd.android.package-archive',
            ]);
    }
}
