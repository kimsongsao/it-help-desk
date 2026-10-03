<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->jsonb('permissions')->default('[]');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $staffPermissions = [
            'tickets.view_all', 'tickets.create', 'tickets.manage', 'tickets.assign', 'tickets.work',
            'tickets.comment_internal', 'tickets.escalate', 'api.access',
        ];

        $roles = [
            ['name' => 'Administrator', 'slug' => 'admin', 'description' => 'Full access to every part of the help desk.', 'permissions' => []],
            ['name' => 'Supervisor', 'slug' => 'supervisor', 'description' => 'Leads the support team, receives escalations and configures SLAs and workflows.', 'permissions' => [
                ...$staffPermissions, 'reports.view', 'sla.manage', 'workflows.manage', 'categories.manage',
            ]],
            ['name' => 'Support Agent', 'slug' => 'agent', 'description' => 'Works and resolves tickets.', 'permissions' => $staffPermissions],
            ['name' => 'Requester', 'slug' => 'requester', 'description' => 'Employees who submit tickets.', 'permissions' => ['tickets.create', 'api.access']],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->insert([
                ...$role,
                'permissions' => json_encode($role['permissions']),
                'is_system' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('email')->constrained()->restrictOnDelete();
        });

        DB::statement('UPDATE users SET role_id = roles.id FROM roles WHERE roles.slug = users.role');
        DB::statement("UPDATE users SET role_id = (SELECT id FROM roles WHERE slug = 'requester') WHERE role_id IS NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable(false)->change();
            $table->dropColumn('role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('requester')->index()->after('email');
        });

        DB::statement('UPDATE users SET role = roles.slug FROM roles WHERE roles.id = users.role_id');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });

        Schema::dropIfExists('roles');
    }
};
