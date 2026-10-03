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
     * Free-text departments on users become department records, and existing supervisors may manage teams.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('team_id')->nullable()->after('assignee_id')->constrained()->nullOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('default_team_id')->nullable()->after('icon')->constrained('teams')->nullOnDelete();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->after('role_id')->constrained()->nullOnDelete();
        });

        DB::statement(<<<'SQL'
            INSERT INTO departments (organization_id, name, created_at, updated_at)
            SELECT DISTINCT organization_id, trim(department), now(), now()
            FROM users
            WHERE department IS NOT NULL AND trim(department) <> ''
            SQL);

        DB::statement(<<<'SQL'
            UPDATE users SET department_id = departments.id
            FROM departments
            WHERE departments.organization_id = users.organization_id AND departments.name = trim(users.department)
            SQL);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('department');
        });

        DB::statement(<<<'SQL'
            UPDATE roles SET permissions = permissions || '["teams.manage"]'::jsonb
            WHERE slug = 'supervisor' AND NOT permissions @> '["teams.manage"]'::jsonb
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('department')->nullable()->after('email');
        });

        DB::statement('UPDATE users SET department = departments.name FROM departments WHERE departments.id = users.department_id');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('department_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_team_id');
        });

        Schema::table('tickets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
        });

        DB::statement("UPDATE roles SET permissions = permissions - 'teams.manage'");
    }
};
