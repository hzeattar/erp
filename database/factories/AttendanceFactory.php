<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'branch_id' => Branch::factory(),
            'date' => now()->toDateString(),
            'status' => 'Present',
        ];
    }
}
