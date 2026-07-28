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
        Schema::create('paiement_creances', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // auto-generated if not provided i.e CLPAI-123456
            $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
            $table->foreignId('creance_id')->constrained('creances')->cascadeOnDelete();
            $table->decimal('montant', 15, 2);
            $table->string('mode_paiement')->nullable();
            $table->timestamp('date_paiement')->nullable();
            $table->text('commentaire')->nullable();
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
        Schema::dropIfExists('paiement_creances');
    }
};
