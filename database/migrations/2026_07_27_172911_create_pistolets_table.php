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
        Schema::create('pistolets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pompe_id')->constrained('pompes')->cascadeOnDelete();
            $table->foreignId('hydrocarbure_id')->constrained('hydrocarbures')->cascadeOnDelete();
            $table->string('libelle');
            $table->boolean('is_active')->default(true); // Switch method for active status
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
        Schema::dropIfExists('pistolets');
    }
};
