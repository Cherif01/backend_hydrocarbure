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
        Schema::create('affectation_citernes', function (Blueprint $table) {
            $table->id();
            // employee can be affected to a another citerne only if his status is annuler or terminer
            // The same for a citerne can be affected to a another employee only if his status is annuler or terminer
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete(); // Driver
            $table->foreignId('citerne_id')->constrained('citernes')->cascadeOnDelete(); // Citerne
            $table->date('date_affectation');
            $table->date('date_depart_prevu')->nullable();
            $table->date('date_arrive_prevu')->nullable();
            $table->date('date_depart_reel')->nullable();
            $table->date('date_arrive_reel')->nullable();
            $table->date('date_retour_prevu')->nullable();
            $table->date('date_retour_reel')->nullable();
            $table->string('ville_depart');
            $table->string('ville_destination');
            $table->decimal('longitude_depart', 10, 2)->nullable();
            $table->decimal('latitude_depart', 10, 2)->nullable();
            $table->decimal('longitude_destination', 10, 2)->nullable();
            $table->decimal('latitude_destination', 10, 2)->nullable();
            $table->enum('status', ['en_cours', 'annuler', 'terminer'])->default('en_cours');
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
        Schema::dropIfExists('affectation_citernes');
    }
};
