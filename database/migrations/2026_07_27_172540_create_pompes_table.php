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
        Schema::create('pompes', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique(); // Auto generated if not provided i.e. 'POM01' , 'POM02' ...
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->string('libelle');
            $table->text('description')->nullable();
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
        Schema::dropIfExists('pompes');
    }
};
