<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = (string) env('DEMO_ADMIN_EMAIL', 'admin@erp.test');
        $adminPassword = env('DEMO_ADMIN_PASSWORD');
        $employeeEmail = (string) env('DEMO_EMPLOYEE_EMAIL', 'employee@erp.test');
        $employeePassword = env('DEMO_EMPLOYEE_PASSWORD');

        if (! is_string($adminPassword) || trim($adminPassword) === '') {
            throw new RuntimeException('DEMO_ADMIN_PASSWORD must be configured before seeding demo data.');
        }

        if (! is_string($employeePassword) || trim($employeePassword) === '') {
            throw new RuntimeException('DEMO_EMPLOYEE_PASSWORD must be configured before seeding demo data.');
        }

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name' => 'ERP Admin',
                'password' => $adminPassword,
            ],
        );

        $employeeUser = User::updateOrCreate(
            ['email' => $employeeEmail],
            [
                'name' => 'Test Employee',
                'password' => $employeePassword,
            ],
        );

        $branch = Branch::updateOrCreate(
            ['name' => 'Cairo Main Branch'],
            [
                'latitude' => 30.0444,
                'longitude' => 31.2357,
                'allowed_radius_in_meters' => 200,
            ],
        );

        $employee = Employee::updateOrCreate(
            ['employee_code' => 'EMP-001'],
            [
                'user_id' => $employeeUser->id,
                'name' => 'Test Employee',
            ],
        );

        $employee->branches()->sync([$branch->id]);
    }
}
