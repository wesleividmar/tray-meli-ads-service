<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();

            $table->string('item_id', 32)->unique();
            $table->unsignedBigInteger('seller_id');

            $table->string('title')->nullable();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('currency_id', 8)->nullable();
            $table->string('permalink')->nullable();

            $table->string('status', 32)->nullable();
            $table->timestamp('fetched_at')->nullable();
            $table->json('raw')->nullable();

            $table->timestamps();

            $table->index('seller_id');
            $table->index('status');

            $table->foreign('seller_id')
                ->references('seller_id')
                ->on('sellers')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};