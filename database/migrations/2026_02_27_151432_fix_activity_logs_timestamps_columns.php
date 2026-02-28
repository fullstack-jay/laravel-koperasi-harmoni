<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Change created_at from bigint to timestamp
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN created_at TYPE TIMESTAMP USING (to_timestamp(created_at))');
        DB::statement("ALTER TABLE activity_logs ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP");

        // Change updated_at from bigint to timestamp
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN updated_at TYPE TIMESTAMP USING (to_timestamp(updated_at))');
        DB::statement("ALTER TABLE activity_logs ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert created_at back to bigint
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN created_at TYPE BIGINT USING (EXTRACT(EPOCH FROM created_at)::BIGINT)');
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN created_at SET DEFAULT (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::BIGINT)');

        // Revert updated_at back to bigint
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN updated_at TYPE BIGINT USING (EXTRACT(EPOCH FROM updated_at)::BIGINT)');
        DB::statement('ALTER TABLE activity_logs ALTER COLUMN updated_at SET DEFAULT (EXTRACT(EPOCH FROM CURRENT_TIMESTAMP)::BIGINT)');
    }
};
