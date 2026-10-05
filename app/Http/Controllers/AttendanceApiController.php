<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\GpsAttendanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AttendanceApiController extends Controller
{
    public function __construct(private readonly GpsAttendanceService $gpsAttendance)
    {
    }

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

        $token = $user->createToken($credentials['device_name'] ?? 'attendance-mobile', [
            'attendance:branches',
            'attendance:check-in',
            'attendance:check-out',
        ]);

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

    public function branches(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->setApiLocale($request, $user);

        return response()->json([
            'branches' => $this->gpsAttendance->assignedBranches($user)->map(fn (Branch $branch) => [
                'id' => $branch->id,
                'name' => $branch->name,
                'latitude' => $branch->latitude,
                'longitude' => $branch->longitude,
                'allowed_radius_in_meters' => $branch->allowed_radius_in_meters,
                'maximum_accuracy_in_meters' => $branch->maximum_accuracy_in_meters,
            ])->values(),
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
            'current_accuracy' => ['required', 'numeric', 'between:0,10000'],
        ], $this->locationValidationMessages());

        $branch = Branch::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->find($data['branch_id']);

        if (!$branch) {
            return response()->json(['message' => __('gps_inventory.api.branch_not_found')], 404);
        }

        if (!$this->gpsAttendance->assignedBranch($user, $branch->id)) {
            return response()->json(['message' => __('gps_inventory.api.not_assigned')], 403);
        }

        $verification = $this->gpsAttendance->verify($request, $user, $branch, 'clock_in');

        if (!$verification['valid']) {
            return response()->json([
                'message' => __('gps_inventory.api.' . $verification['reason']),
                'distance_in_meters' => round($verification['distance_in_meters'], 2),
                'allowed_radius_in_meters' => $verification['allowed_radius_in_meters'],
                'maximum_accuracy_in_meters' => $verification['maximum_accuracy_in_meters'],
            ], $verification['reason'] === 'poor_accuracy' ? 422 : 400);
        }

        $now = now($this->companyTimezone($user));
        $existing = Attendance::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereBetween('clock_in_time', [$now->copy()->startOfDay()->utc(), $now->copy()->endOfDay()->utc()])
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
        $attendance->clock_in_time = $now->utc();
        $attendance->clock_in_ip = $request->ip();
        $attendance->working_from = 'office';
        $attendance->work_from_type = 'office';
        $attendance->location_id = $user->employeeDetail?->company_address_id;
        $attendance->latitude = $data['current_lat'];
        $attendance->longitude = $data['current_lng'];
        $attendance->clock_in_accuracy = $data['current_accuracy'];
        $attendance->late = 'no';
        $attendance->half_day = 'no';
        $attendance->save();
        $this->gpsAttendance->recordAccepted($request, $user, $branch, 'clock_in', $attendance, $verification);

        return response()->json([
            'message' => __('gps_inventory.api.checked_in'),
            'attendance_id' => $attendance->id,
            'branch' => $branch->name,
            'distance_in_meters' => round($verification['distance_in_meters'], 2),
        ]);
    }

    public function checkOut(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->setApiLocale($request, $user);

        $data = $request->validate([
            'current_lat' => ['required', 'numeric', 'between:-90,90'],
            'current_lng' => ['required', 'numeric', 'between:-180,180'],
            'current_accuracy' => ['required', 'numeric', 'between:0,10000'],
        ], $this->locationValidationMessages());

        $attendance = Attendance::where('company_id', $user->company_id)
            ->where('user_id', $user->id)
            ->whereNull('clock_out_time')
            ->latest('clock_in_time')
            ->first();

        if (!$attendance) {
            return response()->json(['message' => __('gps_inventory.api.not_checked_in')], 409);
        }

        $branch = $attendance->branch;

        if (!$branch || !$branch->is_active) {
            return response()->json(['message' => __('gps_inventory.api.branch_not_found')], 404);
        }

        $verification = $this->gpsAttendance->verify($request, $user, $branch, 'clock_out', $attendance);

        if (!$verification['valid']) {
            return response()->json([
                'message' => __('gps_inventory.api.' . $verification['reason']),
                'distance_in_meters' => round($verification['distance_in_meters'], 2),
                'allowed_radius_in_meters' => $verification['allowed_radius_in_meters'],
                'maximum_accuracy_in_meters' => $verification['maximum_accuracy_in_meters'],
            ], $verification['reason'] === 'poor_accuracy' ? 422 : 400);
        }

        $attendance->clock_out_time = now($this->companyTimezone($user))->utc();
        $attendance->clock_out_ip = $request->ip();
        $attendance->clock_out_latitude = $data['current_lat'];
        $attendance->clock_out_longitude = $data['current_lng'];
        $attendance->clock_out_accuracy = $data['current_accuracy'];
        $attendance->save();
        $this->gpsAttendance->recordAccepted($request, $user, $branch, 'clock_out', $attendance, $verification);

        return response()->json([
            'message' => __('gps_inventory.api.checked_out'),
            'attendance_id' => $attendance->id,
            'branch' => $branch->name,
            'distance_in_meters' => round($verification['distance_in_meters'], 2),
        ]);
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

    private function companyTimezone(User $user): string
    {
        return Company::withoutGlobalScopes()->find($user->company_id)?->timezone ?? config('app.timezone');
    }

    private function locationValidationMessages(): array
    {
        return [
            'current_lat.required' => __('gps_inventory.api.location_required'),
            'current_lng.required' => __('gps_inventory.api.location_required'),
            'current_accuracy.required' => __('gps_inventory.api.accuracy_required'),
        ];
    }
}
