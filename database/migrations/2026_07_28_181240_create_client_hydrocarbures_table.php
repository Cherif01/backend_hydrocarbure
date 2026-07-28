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
        Schema::create('client_hydrocarbures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('hydrocarbure_id')->constrained('hydrocarbures')->cascadeOnDelete();
            $table->integer('max_litre')->default(0); // Maximum de litre autorisé par client par mois
            $table->decimal('prix')->default(0.00); // Prix par litre qui peut être payé par le client par mois
            $table->boolean('is_active')->default(true); // Est actif
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
        Schema::dropIfExists('client_hydrocarbures');
    }
};
