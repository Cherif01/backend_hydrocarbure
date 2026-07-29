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
        Schema::create('approvisions', function (Blueprint $table) {
            $table->id();
            $table->string('reference'); // auto-generated reference if not provided
            $table->foreignId('affectation_citerne_id')->constrained('affectation_citernes')->cascadeOnDelete();
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->timestamp('date_approvision');
            $table->integer('total_litre_theorique');
            $table->integer('total_litre_reel')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvisions');
    }
};
