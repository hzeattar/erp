<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AttendanceLocationCheck;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class GpsAttendanceService
{
    public function assignedBranches(User $user): Collection
    {
        return Branch::query()
            ->where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereHas('users', fn ($query) => $query->whereKey($user->id))
            ->orderBy('name')
            ->get();
    }

    public function assignedBranch(User $user, int $branchId): ?Branch
    {
        return $this->assignedBranches($user)->firstWhere('id', $branchId);
    }

    /**
     * Verify a browser or mobile GPS reading. The caller must never trust
     * a client-side map or distance calculation for an attendance decision.
     */
    public function verify(
        Request $request,
        User $user,
        Branch $branch,
        string $eventType,
        ?Attendance $attendance = null,
    ): array {
        $latitude = (float) $request->input('current_lat');
        $longitude = (float) $request->input('current_lng');
        $accuracy = $request->filled('current_accuracy')
            ? (float) $request->input('current_accuracy')
            : null;
        $maximumAccuracy = (int) ($branch->maximum_accuracy_in_meters ?: 100);
        $distance = $this->distanceInMeters($latitude, $longitude, (float) $branch->latitude, (float) $branch->longitude);

        if ($accuracy !== null && $accuracy > $maximumAccuracy) {
            $this->record($request, $user, $branch, $eventType, 'rejected', $latitude, $longitude, $accuracy, $distance, 'poor_accuracy', $attendance);

            return [
                'valid' => false,
                'reason' => 'poor_accuracy',
                'distance_in_meters' => $distance,
                'allowed_radius_in_meters' => $branch->allowed_radius_in_meters,
                'maximum_accuracy_in_meters' => $maximumAccuracy,
            ];
        }

        if ($distance > $branch->allowed_radius_in_meters) {
            $this->record($request, $user, $branch, $eventType, 'rejected', $latitude, $longitude, $accuracy, $distance, 'out_of_zone', $attendance);

            return [
                'valid' => false,
                'reason' => 'out_of_zone',
                'distance_in_meters' => $distance,
                'allowed_radius_in_meters' => $branch->allowed_radius_in_meters,
                'maximum_accuracy_in_meters' => $maximumAccuracy,
            ];
        }

        return [
            'valid' => true,
            'distance_in_meters' => $distance,
            'allowed_radius_in_meters' => $branch->allowed_radius_in_meters,
            'maximum_accuracy_in_meters' => $maximumAccuracy,
        ];
    }

    public function recordAccepted(
        Request $request,
        User $user,
        Branch $branch,
        string $eventType,
        Attendance $attendance,
        array $verification,
    ): void {
        $this->record(
            $request,
            $user,
            $branch,
            $eventType,
            'accepted',
            (float) $request->input('current_lat'),
            (float) $request->input('current_lng'),
            $request->filled('current_accuracy') ? (float) $request->input('current_accuracy') : null,
            (float) $verification['distance_in_meters'],
            null,
            $attendance,
        );
    }

    public function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6_371_000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;

        return $earthRadius * 2 * asin(min(1, sqrt($a)));
    }

    private function record(
        Request $request,
        User $user,
        Branch $branch,
        string $eventType,
        string $status,
        float $latitude,
        float $longitude,
        ?float $accuracy,
        float $distance,
        ?string $failureReason,
        ?Attendance $attendance,
    ): void {
        AttendanceLocationCheck::create([
            'company_id' => $user->company_id,
            'attendance_id' => $attendance?->id,
            'user_id' => $user->id,
            'branch_id' => $branch->id,
            'event_type' => $eventType,
            'status' => $status,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy_in_meters' => $accuracy,
            'distance_in_meters' => $distance,
            'allowed_radius_in_meters' => $branch->allowed_radius_in_meters,
            'failure_reason' => $failureReason,
            'ip_address' => $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            'occurred_at' => now(),
        ]);
    }
}
