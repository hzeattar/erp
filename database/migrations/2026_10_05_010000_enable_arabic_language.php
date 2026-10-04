<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('language_settings')
            ->where('language_code', 'ar')
            ->update([
                'status' => 'enabled',
                'is_rtl' => true,
            ]);
    }

    public function down(): void
    {
        DB::table('language_settings')
            ->where('language_code', 'ar')
            ->update(['status' => 'disabled']);
    }
};
