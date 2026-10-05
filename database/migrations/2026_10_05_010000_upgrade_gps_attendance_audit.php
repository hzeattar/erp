<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('branches', 'maximum_accuracy_in_meters')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->unsignedInteger('maximum_accuracy_in_meters')->default(100)->after('allowed_radius_in_meters');
            });
        }

        Schema::table('attendances', function (Blueprint $table) {
            if (! Schema::hasColumn('attendances', 'clock_in_accuracy')) {
                $table->decimal('clock_in_accuracy', 8, 2)->nullable()->after('longitude');
            }

            if (! Schema::hasColumn('attendances', 'clock_out_latitude')) {
                $table->decimal('clock_out_latitude', 10, 7)->nullable()->after('clock_out_time');
            }

            if (! Schema::hasColumn('attendances', 'clock_out_longitude')) {
                $table->decimal('clock_out_longitude', 10, 7)->nullable()->after('clock_out_latitude');
            }

            if (! Schema::hasColumn('attendances', 'clock_out_accuracy')) {
                $table->decimal('clock_out_accuracy', 8, 2)->nullable()->after('clock_out_longitude');
            }
        });

        if (! Schema::hasTable('attendance_location_checks')) {
            Schema::create('attendance_location_checks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('attendance_id')->nullable();
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('branch_id');
            $table->string('event_type', 20);
            $table->string('status', 20);
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->decimal('accuracy_in_meters', 8, 2)->nullable();
            $table->decimal('distance_in_meters', 10, 2);
            $table->unsignedInteger('allowed_radius_in_meters');
            $table->string('failure_reason', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1000)->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->foreign('company_id')->references('id')->on('companies')->cascadeOnDelete();
            $table->foreign('attendance_id')->references('id')->on('attendances')->nullOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            $table->index(['company_id', 'user_id', 'occurred_at']);
            $table->index(['company_id', 'branch_id', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_location_checks');

        $attendanceColumns = collect(['clock_in_accuracy', 'clock_out_latitude', 'clock_out_longitude', 'clock_out_accuracy'])
            ->filter(fn (string $column) => Schema::hasColumn('attendances', $column))
            ->all();

        if ($attendanceColumns !== []) {
            Schema::table('attendances', function (Blueprint $table) use ($attendanceColumns) {
                $table->dropColumn($attendanceColumns);
            });
        }

        if (Schema::hasColumn('branches', 'maximum_accuracy_in_meters')) {
            Schema::table('branches', function (Blueprint $table) {
                $table->dropColumn('maximum_accuracy_in_meters');
            });
        }
    }
};
