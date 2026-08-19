<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EventPhoto;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminPhotoController extends Controller
{
    public function show(EventPhoto $photo): BinaryFileResponse
    {
        abort_unless(Storage::disk('local')->exists($photo->path), 404);

        return response()->file(
            Storage::disk('local')->path($photo->path),
            [
                'Content-Type' => $photo->mime_type ?: 'image/jpeg',
            ]
        );
    }
}
