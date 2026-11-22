<?php

use App\Services\Reports\CompassWeightsFile;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('issues')->exists()) {
            return;
        }

        if (! is_file(CompassWeightsFile::path())) {
            throw new RuntimeException('The reviewed compass weights file is missing.');
        }

        $result = app(CompassWeightsFile::class)->seedFromFile();
        Log::info('Published the reviewed compass weights.', $result);
    }

    public function down(): void
    {
        // Published reviews are content. Keep them and their audit history through a code rollback.
    }
};
