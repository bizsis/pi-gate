<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $device =
            $request->attributes->get('device');

        $employees =
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
            'success' => true,

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
                $employees->map(function ($employee) {

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
        ]);
    }
}
