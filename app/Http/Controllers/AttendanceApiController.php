<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AttendanceApiController extends Controller
{
    public function issueToken(Request $request): JsonResponse
    {
        $this->setApiLocale($request);

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::withoutGlobalScopes()->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password) || !$user->hasRole('employee')) {
            return response()->json(['message' => __('gps_inventory.api.invalid_credentials')], 401);
        }

        $this->setApiLocale($request, $user);

        $token = $user->createToken($credentials['device_name'] ?? 'attendance-mobile', ['attendance:check-in']);

        return response()->json([
            'message' => __('gps_inventory.api.token_issued'),
            'token' => $token->plainTextToken,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function checkIn(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->setApiLocale($request, $user);

        $data = $request->validate([
            'branch_id' => ['required', 'integer'],
            'current_lat' => ['required', 'numeric', 'between:-90,90'],
            'current_lng' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $branch = Branch::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->find($data['branch_id']);

        if (!$branch) {
            return response()->json(['message' => __('gps_inventory.api.branch_not_found')], 404);
        }

        if (!$branch->users()->where('users.id', $user->id)->exists()) {
            return response()->json(['message' => __('gps_inventory.api.not_assigned')], 403);
        }

        $distance = $this->distanceInMeters(
            (float) $data['current_lat'],
            (float) $data['current_lng'],
            (float) $branch->latitude,
            (float) $branch->longitude,
        );

        if ($distance > $branch->allowed_radius_in_meters) {
            return response()->json([
                'message' => __('gps_inventory.api.out_of_zone'),
                'distance_in_meters' => round($distance, 2),
                'allowed_radius_in_meters' => $branch->allowed_radius_in_meters,
            ], 400);
        }

        $today = now()->toDateString();
        $existing = Attendance::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereDate('clock_in_time', $today)
            ->whereNull('clock_out_time')
            ->first();

        if ($existing) {
            return response()->json([
                'message' => __('gps_inventory.api.already_checked_in'),
                'attendance_id' => $existing->id,
            ], 409);
        }

        $attendance = new Attendance();
        $attendance->company_id = $user->company_id;
        $attendance->user_id = $user->id;
        $attendance->branch_id = $branch->id;
        $attendance->clock_in_time = now();
        $attendance->clock_in_ip = $request->ip();
        $attendance->working_from = 'office';
        $attendance->work_from_type = 'office';
        $attendance->location_id = $user->employeeDetail?->company_address_id;
        $attendance->latitude = $data['current_lat'];
        $attendance->longitude = $data['current_lng'];
        $attendance->late = 'no';
        $attendance->half_day = 'no';
        $attendance->save();

        return response()->json([
            'message' => __('gps_inventory.api.checked_in'),
            'attendance_id' => $attendance->id,
            'branch' => $branch->name,
            'distance_in_meters' => round($distance, 2),
        ]);
    }

    private function distanceInMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $latDelta = deg2rad($lat2 - $lat1);
        $lngDelta = deg2rad($lng2 - $lng1);
        $a = sin($latDelta / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($lngDelta / 2) ** 2;
        $centralAngle = 2 * asin(min(1, sqrt($a)));

        return $earthRadius * $centralAngle;
    }

    private function setApiLocale(Request $request, ?User $user = null): void
    {
        $locale = $request->headers->has('Accept-Language')
            ? $request->getPreferredLanguage(['en', 'ar'])
            : $user?->locale;

        if (in_array($locale, ['en', 'ar'], true)) {
            app()->setLocale($locale);
        }
    }
}
