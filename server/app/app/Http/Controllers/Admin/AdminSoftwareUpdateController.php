<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SoftwareUpdate;
use App\Support\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminSoftwareUpdateController extends Controller
{
    public function index(): View
    {
        return view('admin.software-updates.index', [
            'updates' => SoftwareUpdate::query()
                ->orderByDesc('version_code')
                ->paginate(20),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $data = $request->validate([
            'version_code' => ['required', 'integer', 'min:1'],
            'version_name' => ['required', 'string', 'max:50'],
            'apk' => ['required', 'file', 'max:204800'],
            'mandatory' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        SoftwareUpdate::query()->update(['active' => false]);

        $file = $request->file('apk');
        $path = $file->storeAs(
            'software-updates',
            'pi-gate-' . $data['version_code'] . '.apk'
        );

        $update = SoftwareUpdate::query()->create([
            'version_code' => $data['version_code'],
            'version_name' => $data['version_name'],
            'apk_path' => $path,
            'sha256' => hash_file('sha256', Storage::path($path)),
            'file_size' => $file->getSize(),
            'mandatory' => (bool) ($data['mandatory'] ?? true),
            'active' => true,
            'notes' => $data['notes'] ?? null,
        ]);

        AdminAudit::log($request, 'software_update.created', $update, [], $update->toArray());

        return redirect()
            ->route('admin.software-updates')
            ->with('status', 'A PDA szoftverfrissítés kiküldésre előkészítve.');
    }
}
