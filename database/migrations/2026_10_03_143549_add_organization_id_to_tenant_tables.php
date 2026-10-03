<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The tables that hold data belonging to a single organization.
     *
     * @var list<string>
     */
    private array $tables = ['users', 'roles', 'categories', 'tickets', 'ticket_attachments', 'sla_policies', 'workflows'];

    /**
     * Run the migrations.
     *
     * Existing data is moved into a "Default organization" so a single-company install keeps working.
     */
    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable()->after('id')->constrained()->cascadeOnDelete();
            });
        }

        if (DB::table('users')->exists()) {
            $organizationId = DB::table('organizations')->insertGetId([
                'name' => 'Default organization',
                'slug' => 'default',
                'plan' => 'enterprise',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($this->tables as $tableName) {
                DB::table($tableName)->update(['organization_id' => $organizationId]);
            }
        } else {
            DB::table('roles')->whereNull('organization_id')->delete();
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->dropUnique(['slug']);
            $table->unique(['organization_id', 'name']);
            $table->unique(['organization_id', 'slug']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['name']);
            $table->unique(['organization_id', 'name']);
        });

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('organization_id')->nullable(false)->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name']);
            $table->unique('name');
        });

        Schema::table('roles', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'name']);
            $table->dropUnique(['organization_id', 'slug']);
            $table->unique('name');
            $table->unique('slug');
        });

        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropConstrainedForeignId('organization_id');
            });
        }
    }
};
