<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('versements', function (Blueprint $table) {
            $table->timestamp('date_reception')->nullable()->after('date_versement');
        });

        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE versements MODIFY status ENUM('en_cours','rejeter','annuler','recu','confirmer') NOT NULL DEFAULT 'en_cours'");
        }
    }

    public function down(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE versements MODIFY status ENUM('en_cours','rejeter','annuler','confirmer') NOT NULL DEFAULT 'en_cours'");
        }

        Schema::table('versements', function (Blueprint $table) {
            $table->dropColumn('date_reception');
        });
    }
};

