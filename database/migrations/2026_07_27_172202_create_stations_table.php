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
        Schema::create('stations', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Auto generated if not provided i.e. 'STA233456'
            $table->string('libelle');
            $table->text('description')->nullable();
            $table->string('adresse')->nullable();
            $table->string('ville')->nullable();
            $table->decimal('longitude', 15, 2)->nullable();
            $table->decimal('latitude', 15, 2)->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true); // Switch method for active status
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stations');
    }
};
