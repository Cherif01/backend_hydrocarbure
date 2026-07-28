<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('type_operations', function (Blueprint $table) {
            $table->id();
            $table->string('libelle', 255)->unique();
            $table->text('description')->nullable();
            $table->boolean('nature')->default(false); // true or 1 => entry (incoming money), false or 0 => exit (outgoing money)
            $table->boolean('is_active')->default(true); // true or 1 => active, false or 0 => inactive
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('type_operations');
    }
};
