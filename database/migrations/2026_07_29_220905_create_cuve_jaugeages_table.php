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
        Schema::create('cuve_jaugeages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cuve_id')->constrained('cuves')->cascadeOnDelete();
            $table->timestamp('date_jauge');
            $table->decimal('valeur_jauge', 10, 2)->default(0);
            $table->decimal('volume_reel', 10, 2)->default(0);
            $table->decimal('volume_theorique', 10, 2)->default(0);
            $table->text('commentaire')->nullable();
            // in resource calculate ecart (volume_theorique - volume_reel)
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
        Schema::dropIfExists('cuve_jaugeages');
    }
};
