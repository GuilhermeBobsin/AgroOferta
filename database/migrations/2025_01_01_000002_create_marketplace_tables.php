<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('slug')->unique();
            $t->timestamps();
        });

        Schema::create('listings', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->foreignId('category_id')->constrained();
            $t->string('title');
            $t->text('description')->nullable();
            $t->decimal('price', 12, 2);
            $t->string('unit', 20)->default('un'); // kg, saca, litro, un...
            $t->decimal('quantity', 12, 2)->nullable();
            $t->boolean('negotiable')->default(true);
            $t->string('image_path')->nullable();
            $t->string('city');
            $t->string('state', 2);
            $t->decimal('latitude', 10, 7)->nullable();
            $t->decimal('longitude', 10, 7)->nullable();
            $t->string('status', 20)->default('active'); // active, sold, paused
            $t->timestamps();
            $t->index(['status', 'state']);
            $t->index(['latitude', 'longitude']);
        });

        Schema::create('negotiations', function (Blueprint $t) {
            $t->id();
            $t->foreignId('listing_id')->constrained()->cascadeOnDelete();
            $t->foreignId('buyer_id')->constrained('users')->cascadeOnDelete();
            $t->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $t->timestamps();
            $t->unique(['listing_id', 'buyer_id']);
        });

        Schema::create('messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('negotiation_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->text('body');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('messages');
        Schema::dropIfExists('negotiations');
        Schema::dropIfExists('listings');
        Schema::dropIfExists('categories');
    }
};
