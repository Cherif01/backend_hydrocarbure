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
        Schema::create('creances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('affectation_pistolet_id')->nullable()->constrained('affectation_pistolets')->nullOnDelete();
            $table->timestamp('date_creance');
            $table->integer('total_litre')->default(0);
            $table->decimal('montant', 15, 2)->default(0); // total_litre * affectation_pistolet.prix_vente_jour
            $table->text('commentaire')->nullable();
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
        Schema::dropIfExists('creances');
    }
};
