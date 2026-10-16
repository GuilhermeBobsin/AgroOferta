<?php

use App\Support\BrazilLocation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('listings')
            ->where(function ($query) {
                $query->whereNull('latitude')->orWhereNull('longitude');
            })
            ->orderBy('id')
            ->eachById(function (object $listing): void {
                $coordinates = BrazilLocation::coordinates($listing->state, $listing->city);

                if ($coordinates) {
                    DB::table('listings')->where('id', $listing->id)->update([
                        'latitude' => $coordinates[0],
                        'longitude' => $coordinates[1],
                    ]);
                }
            });
    }

    public function down(): void
    {
        // Não removemos coordenadas no rollback: algumas podem ter sido atualizadas manualmente depois.
    }
};
