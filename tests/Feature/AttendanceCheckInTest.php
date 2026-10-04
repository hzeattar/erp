<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceCheckInTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_check_in_from_an_allowed_branch(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $branch = Branch::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'allowed_radius_in_meters' => 100,
        ]);
        $employee->branches()->attach($branch);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/attendance/check-in', [
            'branch_id' => $branch->id,
            'current_lat' => 30.0444,
            'current_lng' => 31.2357,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('attendance.status', 'Present');

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'branch_id' => $branch->id,
            'status' => 'Present',
        ]);

        $this->postJson('/api/attendance/check-in', [
            'branch_id' => $branch->id,
            'current_lat' => 30.0444,
            'current_lng' => 31.2357,
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Attendance was already checked in for today.');

        $this->assertDatabaseCount('attendances', 1);
    }

    public function test_employee_cannot_check_in_at_an_unassigned_branch(): void
    {
        $user = User::factory()->create();
        Employee::factory()->create(['user_id' => $user->id]);
        $branch = Branch::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/attendance/check-in', [
            'branch_id' => $branch->id,
            'current_lat' => $branch->latitude,
            'current_lng' => $branch->longitude,
        ])->assertForbidden();

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_employee_cannot_check_in_outside_the_branch_zone(): void
    {
        $user = User::factory()->create();
        $employee = Employee::factory()->create(['user_id' => $user->id]);
        $branch = Branch::factory()->create([
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'allowed_radius_in_meters' => 50,
        ]);
        $employee->branches()->attach($branch);

        Sanctum::actingAs($user);

        $this->postJson('/api/attendance/check-in', [
            'branch_id' => $branch->id,
            'current_lat' => 30.0500,
            'current_lng' => 31.2357,
        ])
            ->assertStatus(400)
            ->assertJsonPath('message', 'Out of branch zone');

        $this->assertDatabaseCount('attendances', 0);
    }

    public function test_user_can_issue_a_sanctum_token_for_a_mobile_device(): void
    {
        User::factory()->create([
            'email' => 'mobile@example.com',
            'password' => 'secret-password',
        ]);

        $response = $this->postJson('/api/auth/token', [
            'email' => 'mobile@example.com',
            'password' => 'secret-password',
            'device_name' => 'Android test device',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonStructure(['token', 'token_type']);
    }
}
