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
        Schema::create('appro_compartiment_jauges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('approvision_id')->constrained('approvisions')->cascadeOnDelete();
            $table->foreignId('hydrocarbure_id')->constrained('hydrocarbures')->cascadeOnDelete();
            $table->integer('num_compartiment');
            $table->decimal('valeur_jauge', 10, 2)->default(0);
            $table->decimal('volume_reel', 10, 2)->default(0);
            $table->decimal('volume_theorique', 10, 2)->default(0);
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
        Schema::dropIfExists('appro_compartiment_jauges');
    }
};
