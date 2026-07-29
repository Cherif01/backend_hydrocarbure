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
        Schema::create('versements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caisse_id')->constrained('caisses')->cascadeOnDelete();
            $table->foreignId('compte_id')->constrained('comptes')->cascadeOnDelete();
            $table->enum('type', ['direct', 'indirect'])->default('direct');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // required if type = indirect (versement passer par un autre utilisateur)
            $table->decimal('montant', 15, 2)->default(0.00);
            $table->timestamp('date_versement');
            $table->text('commentaire')->nullable();
            $table->enum('status', ['en_cours', 'rejeter', 'annuler', 'confirmer'])->default('en_cours');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('versements');
    }
};
