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
        Schema::create('cuves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('station_id')->constrained('stations')->cascadeOnDelete();
            $table->foreignId('hydrocarbure_id')->constrained('hydrocarbures')->cascadeOnDelete();
            $table->string('reference')->unique(); // auto-generated if not provided | CUV-345256
            $table->string('libelle');
            $table->integer('capacite')->default(0);
            $table->boolean('is_active')->default(true);
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
        Schema::dropIfExists('cuves');
    }
};
