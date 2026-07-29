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
        Schema::create('citerne_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('citerne_id')->constrained('citernes')->cascadeOnDelete();
            $table->enum('type_document', ['agrement_transport', 'controle_technique', 'assurance', 'certificat_jaugeage']);
            $table->string('numero_document')->nullable();
            $table->date('date_emission');
            $table->date('date_expiration');
            $table->string('fichier_scan')->nullable();
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
        Schema::dropIfExists('citerne_documents');
    }
};
