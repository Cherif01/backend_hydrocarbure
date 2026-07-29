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
        Schema::create('affectation_citerne_depenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affectation_citerne_id')->constrained('affectation_citernes')->cascadeOnDelete();
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->decimal('montant', 15, 2);
            $table->date('date_depense');
            $table->string('facture')->nullable();
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
        Schema::dropIfExists('affectation_citerne_depenses');
    }
};
