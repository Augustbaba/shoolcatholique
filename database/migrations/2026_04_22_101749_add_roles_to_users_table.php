<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ajoute les rôles : directeur, censeur, econome, prefet, saisisseur
     * à l'enum existant qui contenait : admin, enseignant, parent
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM(
                'admin',
                'enseignant',
                'parent',
                'directeur',
                'censeur',
                'econome',
                'prefet',
                'saisisseur'
            ) NOT NULL DEFAULT 'parent'
        ");
    }

    public function down(): void
    {
        // ⚠️ Attention : les utilisateurs ayant les nouveaux rôles
        // seront en erreur si on revient en arrière
        DB::statement("
            ALTER TABLE users
            MODIFY COLUMN role ENUM(
                'admin',
                'enseignant',
                'parent'
            ) NOT NULL DEFAULT 'parent'
        ");
    }
};
