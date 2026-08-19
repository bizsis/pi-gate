<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Device;
use App\Models\Employee;
use App\Models\Event;
use App\Support\AdminAudit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminEventController extends Controller
{
    public function edit(Event $event): View
    {
        $event->load(['company', 'employee', 'card', 'device', 'photos']);

        return view('admin.events.form', [
            'event' => $event,
            'employees' => Employee::query()
                ->where('company_id', $event->company_id)
                ->orderBy('name')
                ->get(['id', 'name']),
            'cards' => Card::query()
                ->where('company_id', $event->company_id)
                ->orderBy('card_number')
                ->get(['id', 'employee_id', 'card_number']),
            'devices' => Device::query()
                ->where('company_id', $event->company_id)
                ->orderBy('name')
                ->orderBy('device_uid')
                ->get(['id', 'name', 'device_uid']),
        ]);
    }

    public function update(Request $request, Event $event): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(fn ($query) => $query->where('company_id', $event->company_id)),
            ],
            'card_id' => [
                'nullable',
                'integer',
                Rule::exists('cards', 'id')->where(fn ($query) => $query->where('company_id', $event->company_id)),
            ],
            'device_id' => [
                'required',
                'integer',
                Rule::exists('devices', 'id')->where(fn ($query) => $query->where('company_id', $event->company_id)),
            ],
            'event_type' => ['required', Rule::in(['IN', 'OUT'])],
            'event_at' => ['required', 'date'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        if (! empty($data['card_id'])) {
            $card = Card::query()->find($data['card_id']);

            if ($card && (int) $card->employee_id !== (int) $data['employee_id']) {
                return back()
                    ->withErrors(['card_id' => 'A kártya nem ehhez a dolgozóhoz tartozik.'])
                    ->withInput();
            }
        }

        $oldValues = $event->only(array_keys($data));
        $event->update($data);

        AdminAudit::log($request, 'event.updated', $event, $oldValues, $event->fresh()->toArray());

        return redirect()
            ->route('admin.events')
            ->with('status', 'A blokkolás adatai frissültek.');
    }

    public function destroy(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $event->load('photos');
        $oldValues = $event->toArray();

        foreach ($event->photos as $photo) {
            if (Storage::disk('local')->exists($photo->path)) {
                Storage::disk('local')->delete($photo->path);
            }

            $photo->delete();
        }

        $event->delete();

        AdminAudit::log($request, 'event.deleted', $event, $oldValues, null);

        return redirect()
            ->route('admin.events')
            ->with('status', 'A blokkolás törölve lett.');
    }
}
