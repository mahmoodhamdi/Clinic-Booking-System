<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = $connection->select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }

            return false;
        }

        // For MySQL
        $indexes = $connection->select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);

        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Vacations - date indexes for slot availability checks.
        // The vacations table stores `start_date`/`end_date` (no `date` column),
        // and there's no `is_active` flag (vacations are deletes/upserts, not toggles).
        // Earlier versions of this migration referenced columns that don't exist;
        // only the start_date+end_date pair benefits from an explicit index since
        // the create-table migration already covers individual columns.
        if (! $this->indexExists('vacations', 'vacations_dates_range_index')) {
            Schema::table('vacations', function (Blueprint $table) {
                $table->index(['start_date', 'end_date'], 'vacations_dates_range_index');
            });
        }

        // Notifications - read status and user index
        if (! $this->indexExists('notifications', 'notifications_notifiable_read_index')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['notifiable_id', 'read_at'], 'notifications_notifiable_read_index');
            });
        }

        // Appointments - additional index for date-based queries
        if (! $this->indexExists('appointments', 'appointments_date_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->index('appointment_date', 'appointments_date_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if ($this->indexExists('vacations', 'vacations_dates_range_index')) {
            Schema::table('vacations', function (Blueprint $table) {
                $table->dropIndex('vacations_dates_range_index');
            });
        }

        if ($this->indexExists('notifications', 'notifications_notifiable_read_index')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('notifications_notifiable_read_index');
            });
        }

        if ($this->indexExists('appointments', 'appointments_date_index')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropIndex('appointments_date_index');
            });
        }
    }
};
