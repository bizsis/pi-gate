<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\Event;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $events =
            Event::query()
                ->where(
                    'company_id',
                    $device->company_id
                )
                ->where(
                    'device_id',
                    $device->id
                )
                ->whereNotNull(
                    'client_event_uuid'
                )
                ->with([
                    'card',
                ])
                ->orderByDesc(
                    'event_at'
                )
                ->limit(1000)
                ->get();

        return response()->json([
            'success' => true,

            'events' =>
                $events->map(function (Event $event) {

                    return [
                        'id' =>
                            $event->id,

                        'client_event_uuid' =>
                            $event->client_event_uuid,

                        'employee_id' =>
                            $event->employee_id,

                        'card_number' =>
                            $event->card?->card_number,

                        'event_type' =>
                            $event->event_type,

                        'event_at' =>
                            $event->event_at,

                        'latitude' =>
                            $event->latitude !== null
                                ? (float) $event->latitude
                                : null,

                        'longitude' =>
                            $event->longitude !== null
                                ? (float) $event->longitude
                                : null,

                        'updated_at' =>
                            $event->updated_at,
                    ];
                }),
        ]);
    }

    public function batch(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $validated = $request->validate([
            'events' => [
                'required',
                'array',
                'max:500',
            ],

            'events.*.client_event_uuid' => [
                'required',
                'string',
                'max:255',
            ],

            'events.*.employee_id' => [
                'required',
                'integer',
            ],

            'events.*.card_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'events.*.event_type' => [
                'required',
                'in:IN,OUT',
            ],

            'events.*.event_at' => [
                'required',
                'date',
            ],

            'events.*.latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],

            'events.*.longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
        ]);

        $created = 0;
        $duplicate = 0;
        $failed = [];

        foreach ($validated['events'] as $index => $eventData) {

            try {

                $alreadyExists =
                    Event::query()
                        ->where(
                            'client_event_uuid',
                            $eventData['client_event_uuid']
                        )
                        ->exists();

                if ($alreadyExists) {
                    $duplicate++;
                    continue;
                }

                $employee =
                    Employee::query()
                        ->where('id', $eventData['employee_id'])
                        ->where('company_id', $device->company_id)
                        ->where('active', true)
                        ->first();

                if (!$employee) {

                    $failed[] = [
                        'index' => $index,
                        'client_event_uuid' =>
                            $eventData['client_event_uuid'],
                        'message' =>
                            'A dolgozó nem található vagy inaktív.',
                    ];

                    continue;
                }

                $card = null;

                if (!empty($eventData['card_number'])) {

                    $card =
                        Card::query()
                            ->where(
                                'company_id',
                                $device->company_id
                            )
                            ->where(
                                'employee_id',
                                $employee->id
                            )
                            ->where(
                                'card_number',
                                $eventData['card_number']
                            )
                            ->first();
                }

                DB::transaction(function () use (
                    $device,
                    $employee,
                    $card,
                    $eventData
                ) {

                    Event::create([
                        'company_id' =>
                            $device->company_id,

                        'device_id' =>
                            $device->id,

                        'employee_id' =>
                            $employee->id,

                        'card_id' =>
                            $card?->id,

                        'event_type' =>
                            $eventData['event_type'],

                        'event_at' =>
                            $eventData['event_at'],

                        'latitude' =>
                            $eventData['latitude'] ?? null,

                        'longitude' =>
                            $eventData['longitude'] ?? null,

                        'client_event_uuid' =>
                            $eventData['client_event_uuid'],

                        'received_at' =>
                            now(),
                    ]);
                });

                $created++;

            } catch (\Throwable $e) {

                $failed[] = [
                    'index' => $index,
                    'client_event_uuid' =>
                        $eventData['client_event_uuid'] ?? null,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => empty($failed),

            'result' => [
                'received' =>
                    count($validated['events']),

                'created' =>
                    $created,

                'duplicates' =>
                    $duplicate,

                'failed' =>
                    count($failed),
            ],

            'errors' =>
                $failed,
        ]);
    }
}
