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
        Schema::create('citerne_maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citerne_id')->constrained('citernes')->cascadeOnDelete();
            $table->enum('type_maintenance', ['preventive', 'corrective', 'reglementaire']);
            $table->string('nature'); // ex: vidange, freins, pneus, révision jauge
            $table->text('description')->nullable();

            $table->date('date_prevue')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();

            $table->integer('kilometrage_intervention')->nullable();
            $table->decimal('cout', 12, 2)->nullable();
            $table->string('prestataire')->nullable(); // garage/atelier
            $table->string('facture_scan')->nullable();

            $table->enum('status', ['planifiee', 'en_cours', 'terminee', 'annulee'])->default('planifiee');
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
        Schema::dropIfExists('citerne_maintenances');
    }
};
