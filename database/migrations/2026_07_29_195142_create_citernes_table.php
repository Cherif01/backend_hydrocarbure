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
        Schema::create('citernes', function (Blueprint $table) {
            $table->id();
            $table->string('immatriculation')->unique();
            $table->enum('type_citerne', ['camion_citerne', 'semi_remorque', 'remorque']);
            $table->string('marque')->nullable();
            $table->string('modele')->nullable();
            $table->enum('statut', ['disponible', 'en_mission', 'en_maintenance', 'hors_service'])->default('disponible');
            $table->enum('etat', ['interne', 'externne'])->default('interne'); // externe pour les citerne loués
            $table->integer('annee_fabrication')->nullable();
            $table->decimal('capacite_nominale_litres', 12, 2);
            $table->decimal('capacite_utile_litres', 12, 2)->nullable();
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('citernes');
    }
};
