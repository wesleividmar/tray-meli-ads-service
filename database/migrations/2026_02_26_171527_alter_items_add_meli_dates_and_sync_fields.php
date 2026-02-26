<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->timestampTz('meli_created_at')->nullable()->after('status');
            $table->timestampTz('meli_updated_at')->nullable()->after('meli_created_at');

            $table->string('sync_status', 32)->default('pending')->index()->after('fetched_at');
            $table->timestampTz('synced_at')->nullable()->after('sync_status');
            $table->text('last_error')->nullable()->after('synced_at');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn(['meli_created_at', 'meli_updated_at', 'sync_status', 'synced_at', 'last_error']);
        });
    }
};