<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Card;
use App\Models\DeviceSyncLog;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeSyncController extends Controller
{
    public function sync(Request $request): JsonResponse
    {
        $device = $request->attributes->get('device');

        $validated = $request->validate([
            'employees' => [
                'required',
                'array',
                'max:500',
            ],

            'employees.*.id' => [
                'nullable',
                'integer',
            ],

            'employees.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'employees.*.card_number' => [
                'required',
                'string',
                'max:255',
            ],

            'employees.*.active' => [
                'required',
                'boolean',
            ],
        ]);

        $created = 0;
        $updated = 0;
        $serverOwned = 0;
        $failed = [];

        foreach ($validated['employees'] as $index => $employeeData) {

            try {

                DB::transaction(function () use (
                    $device,
                    $employeeData,
                    &$created,
                    &$updated,
                    &$serverOwned
                ) {

                    $employee = null;

                    /*
                     * Ha a PDA olyan ID-t küld,
                     * ami már létezik ennél a cégnél,
                     * akkor azt szerveroldali rekordként kezeljük.
                     */
                    if (!empty($employeeData['id'])) {

                        $employee =
                            Employee::query()
                                ->where(
                                    'company_id',
                                    $device->company_id
                                )
                                ->where(
                                    'id',
                                    $employeeData['id']
                                )
                                ->first();
                    }

                    /*
                     * Ha ID alapján nincs találat,
                     * megnézzük a kártyaszámot.
                     */
                    if (!$employee) {

                        $existingCard =
                            Card::query()
                                ->where(
                                    'company_id',
                                    $device->company_id
                                )
                                ->where(
                                    'card_number',
                                    $employeeData['card_number']
                                )
                                ->first();

                        if ($existingCard) {

                            $employee =
                                Employee::query()
                                    ->where(
                                        'company_id',
                                        $device->company_id
                                    )
                                    ->where(
                                        'id',
                                        $existingCard->employee_id
                                    )
                                    ->first();
                        }
                    }

                    if (!$employee) {

                        $employee =
                            Employee::create([
                                'company_id' =>
                                    $device->company_id,

                                'name' =>
                                    $employeeData['name'],

                                'active' =>
                                    $employeeData['active'],
                            ]);

                        Card::create([
                            'company_id' =>
                                $device->company_id,

                            'employee_id' =>
                                $employee->id,

                            'card_number' =>
                                $employeeData['card_number'],

                            'active' =>
                                true,
                        ]);

                        $created++;

                        return;
                    }

                    /*
                     * Meglévő dolgozó/kártya esetén az admin felület
                     * a központi törzsadat. A PDA régi helyi adata
                     * nem írhatja felül az adminban javított nevet,
                     * aktív állapotot vagy kártyaszámot.
                     */
                    $serverOwned++;
                });

            } catch (\Throwable $e) {

                $failed[] = [
                    'index' => $index,
                    'name' =>
                        $employeeData['name'] ?? null,
                    'card_number' =>
                        $employeeData['card_number'] ?? null,
                    'message' =>
                        $e->getMessage(),
                ];
            }
        }

        $device->forceFill([
            'last_sync_at' => now(),
            'last_seen_at' => now(),
        ])->save();

        DeviceSyncLog::create([
            'device_id' => $device->id,
            'sync_type' => 'employees',
            'status' => empty($failed) ? 'success' : 'partial_failed',
            'sent_events' => 0,
            'received_employees' => $created,
            'message' => sprintf(
                'PDA feltöltés: %d új dolgozó átvéve, %d meglévő szerveroldali rekord érintetlenül hagyva.',
                $created,
                $serverOwned
            ),
            'started_at' => now(),
            'finished_at' => now(),
        ]);

        $serverEmployees =
            $device->company
                ->employees()
                ->where('active', true)
                ->with([
                    'cards' => function ($query) {
                        $query
                            ->where('active', true)
                            ->orderBy('id');
                    }
                ])
                ->orderBy('name')
                ->get();

        return response()->json([
            'success' =>
                empty($failed),

            'result' => [
                'received' =>
                    count($validated['employees']),

                'created' =>
                    $created,

                'updated' =>
                    $updated,

                'server_owned' =>
                    $serverOwned,

                'failed' =>
                    count($failed),
            ],

            'company' => [
                'id' => $device->company->id,
                'name' => $device->company->name,
                'short_name' => $device->company->short_name,
                'tax_number' => $device->company->tax_number,
                'registration_number' => $device->company->registration_number,
                'email' => $device->company->email,
                'phone' => $device->company->phone,
                'address' => $device->company->address,
                'active' => $device->company->active,
                'updated_at' => $device->company->updated_at,
            ],

            'employees' =>
                $serverEmployees->map(function ($employee) {

                    return [
                        'id' => $employee->id,
                        'name' => $employee->name,
                        'external_id' => $employee->external_id,
                        'active' => $employee->active,
                        'updated_at' => $employee->updated_at,

                        'cards' =>
                            $employee->cards->map(function ($card) {

                                return [
                                    'id' => $card->id,
                                    'card_number' => $card->card_number,
                                    'active' => $card->active,
                                    'valid_from' => $card->valid_from,
                                    'valid_until' => $card->valid_until,
                                    'updated_at' => $card->updated_at,
                                ];
                            }),
                    ];
                }),

            'errors' =>
                $failed,
        ]);
    }
}
