<?php

use App\Support\BrazilLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('location_precision', 10)->nullable()->after('longitude');
        });
        Schema::table('listings', function (Blueprint $table) {
            $table->string('location_precision', 10)->nullable()->after('longitude');
        });

        DB::table('users')->whereNotNull('latitude')->whereNotNull('longitude')
            ->update(['location_precision' => 'exact']);

        DB::table('listings')->whereNotNull('latitude')->whereNotNull('longitude')
            ->orderBy('id')->eachById(function (object $listing): void {
                $cityCoordinates = BrazilLocation::coordinates($listing->state, $listing->city);
                $precision = $cityCoordinates
                    && round((float) $listing->latitude, 7) === round($cityCoordinates[0], 7)
                    && round((float) $listing->longitude, 7) === round($cityCoordinates[1], 7)
                    ? 'city'
                    : 'exact';

                DB::table('listings')->where('id', $listing->id)->update([
                    'location_precision' => $precision,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('listings', fn (Blueprint $table) => $table->dropColumn('location_precision'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('location_precision'));
    }
};
