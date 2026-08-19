<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Device;
use App\Support\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminDeviceController extends Controller
{
    public function edit(Device $device): View
    {
        return view('admin.devices.form', [
            'device' => $device,
            'companies' => Company::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Device $device): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['nullable', 'string', 'max:255'],
            'platform' => ['required', 'string', 'max:50'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'active' => ['required', Rule::in(['0', '1'])],
        ]);

        $oldValues = $device->only(array_keys($data));
        $device->update($data);

        AdminAudit::log($request, 'device.updated', $device, $oldValues, $device->fresh()->toArray());

        return redirect()
            ->route('admin.devices')
            ->with('status', 'Az eszköz adatai frissültek.');
    }

    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $oldValues = $device->toArray();

        $device->update([
            'active' => false,
        ]);

        AdminAudit::log($request, 'device.deleted', $device, $oldValues, $device->fresh()->toArray());

        return redirect()
            ->route('admin.devices')
            ->with('status', 'Az eszköz inaktiválva lett.');
    }
}
