<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('reputacao_nota', 3, 2)->nullable();
        });

        DB::table('reviews')
            ->select('reviewed_id')
            ->selectRaw('AVG(rating) as average_rating')
            ->groupBy('reviewed_id')
            ->get()
            ->each(function (object $reviewSummary): void {
                DB::table('users')
                    ->where('id', $reviewSummary->reviewed_id)
                    ->update(['reputacao_nota' => $reviewSummary->average_rating]);
            });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('reputacao_nota');
        });
    }
};
