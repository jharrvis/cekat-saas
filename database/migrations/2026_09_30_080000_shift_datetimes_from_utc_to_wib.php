<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time shift of every datetime/timestamp value from UTC to WIB.
 *
 * The app stored timestamps in UTC (APP_TIMEZONE=UTC) but rendered them
 * without conversion, while emails labelled them "WIB" - every chat time
 * was 7 hours off. APP_TIMEZONE is now Asia/Jakarta, so this migration
 * rewrites stored wall-clock values to match the new interpretation.
 * The absolute instant of every timestamp is preserved (label + value
 * change together).
 *
 * MUST run in the same deploy as the .env change to APP_TIMEZONE=Asia/Jakarta.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->shift(7);
    }

    public function down(): void
    {
        $this->shift(-7);
    }

    protected function shift(int $hours): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        foreach (DB::select('SHOW TABLES') as $row) {
            $table = array_values((array) $row)[0];

            if ($table === 'migrations') {
                continue;
            }

            foreach (DB::select('SHOW COLUMNS FROM `' . $table . '`') as $col) {
                $type = $col->Type ?? '';
                if (! str_contains($type, 'datetime') && ! str_contains($type, 'timestamp')) {
                    continue;
                }

                $field = $col->Field;
                DB::statement(
                    "UPDATE `{$table}` SET `{$field}` = DATE_ADD(`{$field}`, INTERVAL {$hours} HOUR) WHERE `{$field}` IS NOT NULL"
                );
            }
        }
    }
};
