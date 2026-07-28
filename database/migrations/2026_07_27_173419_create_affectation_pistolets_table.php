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
        Schema::create('affectation_pistolets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete(); // Can have only one active affectation at a time
            $table->foreignId('pistolet_id')->constrained('pistolets')->cascadeOnDelete(); // Can have only one active affectation at a time
            $table->decimal('index_ouverture', 15, 2)->default(0); // required on create
            $table->decimal('index_fermeture', 15, 2)->default(0); // required on close
            $table->decimal('litre_vendu', 15, 2)->default(0); // Calculated from index_ouverture - index_fermeture
            $table->decimal('prix_vente_jour', 15, 2)->default(0); // Is pistolet.hydrocarbure.prix_vente
            $table->decimal('litre_retouner', 15, 2)->default(0); // required on close, min 0
            $table->decimal('montant_attentu', 15, 2)->default(0); // (litre_vendu - litre_retourner) * prix_vente_jour
            $table->decimal('montant_recu', 15, 2)->default(0); // required on close, min 0
            $table->text('commentaire')->nullable();
            $table->boolean('is_active')->default(true); // in closeStatus (is_active turn to false) index_fermeture, litre_retourner, and montant_recu are required, commentaire is nullable
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
        Schema::dropIfExists('affectation_pistolets');
    }
};
