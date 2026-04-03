<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // Change ALL string columns to longer lengths
            $table->string('name', 500)->change();           // Place name
            $table->string('location', 100)->change();      // Full address/location
            $table->string('photo', 1000)->change();         // Image URL (can be very long)
            $table->string('map_url', 1000)->change();       // Google Maps URL (can be very long)
            $table->string('open_hours', 30)->change();     // Business hours
            $table->string('category', 10)->change();       // Category name
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            // Revert all columns back to original lengths
            $table->string('name', 255)->change();
            $table->string('location', 255)->change();
            $table->string('photo', 255)->change();
            $table->string('map_url', 255)->change();
            $table->string('open_hours', 255)->change();
            $table->string('category', 255)->change();
        });
    }
};