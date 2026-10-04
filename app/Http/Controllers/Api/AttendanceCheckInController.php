<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceCheckInController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'current_lat' => ['required', 'numeric', 'between:-90,90'],
            'current_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        /** @var Employee|null $employee */
        $employee = $request->user()?->employee;

        if ($employee === null) {
            return response()->json([
                'message' => 'Authenticated user is not linked to an employee profile.',
            ], 403);
        }

        $branch = $employee->branches()
            ->whereKey($validated['branch_id'])
            ->first();

        if ($branch === null) {
            return response()->json([
                'message' => 'This branch is not assigned to the employee.',
            ], 403);
        }

        $distanceInMeters = $this->calculateDistanceInMeters(
            (float) $validated['current_lat'],
            (float) $validated['current_lng'],
            (float) $branch->latitude,
            (float) $branch->longitude,
        );

        if ($distanceInMeters > $branch->allowed_radius_in_meters) {
            return response()->json([
                'message' => 'Out of branch zone',
                'distance_in_meters' => round($distanceInMeters, 2),
                'allowed_radius_in_meters' => $branch->allowed_radius_in_meters,
            ], 400);
        }

        $attendanceDate = now()->toDateString();
        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $attendanceDate)
            ->first();
        $wasRecentlyCreated = false;

        if ($attendance === null) {
            try {
                $attendance = Attendance::create([
                    'employee_id' => $employee->id,
                    'branch_id' => $branch->id,
                    'date' => $attendanceDate,
                    'status' => 'Present',
                ]);
                $wasRecentlyCreated = true;
            } catch (UniqueConstraintViolationException $exception) {
                $attendance = Attendance::query()
                    ->where('employee_id', $employee->id)
                    ->whereDate('date', $attendanceDate)
                    ->first();

                if ($attendance === null) {
                    throw $exception;
                }
            }
        }

        return response()->json([
            'message' => $wasRecentlyCreated
                ? 'Attendance checked in successfully.'
                : 'Attendance was already checked in for today.',
            'attendance' => $attendance->load('branch'),
            'distance_in_meters' => round($distanceInMeters, 2),
        ]);
    }

    private function calculateDistanceInMeters(
        float $currentLatitude,
        float $currentLongitude,
        float $branchLatitude,
        float $branchLongitude,
    ): float {
        $earthRadiusInMeters = 6_371_000;
        $latitudeDifference = deg2rad($branchLatitude - $currentLatitude);
        $longitudeDifference = deg2rad($branchLongitude - $currentLongitude);
        $currentLatitudeInRadians = deg2rad($currentLatitude);
        $branchLatitudeInRadians = deg2rad($branchLatitude);

        $a = sin($latitudeDifference / 2) ** 2
            + cos($currentLatitudeInRadians)
            * cos($branchLatitudeInRadians)
            * sin($longitudeDifference / 2) ** 2;

        return $earthRadiusInMeters * 2 * asin(min(1, sqrt($a)));
    }
}
