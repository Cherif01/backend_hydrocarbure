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
        Schema::create('compte_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('compte_source_id')->constrained('comptes')->cascadeOnDelete();
            $table->foreignId('compte_destination_id')->constrained('comptes')->cascadeOnDelete();
            $table->decimal('montant', 15, 2);
            $table->string('libelle');
            $table->text('commentaire')->nullable();
            $table->timestamp('date_transaction');
            $table->foreignId('created_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->cascadeOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('compte_transactions');
    }
};
