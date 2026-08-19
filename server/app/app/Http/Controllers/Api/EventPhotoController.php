<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventPhoto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EventPhotoController extends Controller
{
    public function upload(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $validated = $request->validate([
            'client_event_uuid' => [
                'required',
                'string',
                'max:255',
            ],

            'photo' => [
                'required',
                'file',
                'image',
                'max:10240',
            ],
        ]);

        $event =
            Event::query()
                ->where(
                    'company_id',
                    $device->company_id
                )
                ->where(
                    'device_id',
                    $device->id
                )
                ->where(
                    'client_event_uuid',
                    $validated['client_event_uuid']
                )
                ->first();

        if (!$event) {

            return response()->json([
                'success' => false,
                'message' => 'Az esemény nem található.',
            ], 404);
        }

        $file =
            $request->file('photo');

        $sha256 =
            hash_file(
                'sha256',
                $file->getRealPath()
            );

        /*
         * Ha ugyanez a kép már felkerült ehhez
         * az eseményhez, nem mentjük el újra.
         */
        $existingPhoto =
            EventPhoto::query()
                ->where(
                    'event_id',
                    $event->id
                )
                ->where(
                    'sha256',
                    $sha256
                )
                ->first();

        if ($existingPhoto) {

            return response()->json([
                'success' => true,
                'duplicate' => true,
                'photo' => [
                    'id' => $existingPhoto->id,
                    'event_id' => $existingPhoto->event_id,
                    'path' => $existingPhoto->path,
                ],
            ]);
        }

        $directory =
            'event-photos/' .
            $device->company_id .
            '/' .
            date('Y/m/d');

        $path =
            $file->store(
                $directory,
                'local'
            );

        $photo =
            EventPhoto::create([
                'event_id' =>
                    $event->id,

                'path' =>
                    $path,

                'original_name' =>
                    $file->getClientOriginalName(),

                'mime_type' =>
                    $file->getMimeType(),

                'file_size' =>
                    $file->getSize(),

                'sha256' =>
                    $sha256,

                'uploaded_at' =>
                    now(),
            ]);

        return response()->json([
            'success' => true,
            'duplicate' => false,
            'photo' => [
                'id' =>
                    $photo->id,

                'event_id' =>
                    $photo->event_id,

                'path' =>
                    $photo->path,

                'file_size' =>
                    $photo->file_size,

                'sha256' =>
                    $photo->sha256,
            ],
        ]);
    }
}
