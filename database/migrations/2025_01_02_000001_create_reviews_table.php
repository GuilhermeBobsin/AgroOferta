<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $t) {
            $t->id();
            $t->foreignId('negotiation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('reviewed_id')->constrained('users')->cascadeOnDelete();
            $t->unsignedTinyInteger('rating'); // 1 a 5
            $t->string('comment', 500)->nullable();
            $t->timestamps();
            $t->unique(['negotiation_id', 'reviewer_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
