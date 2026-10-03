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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('sla_policy_id')->nullable()->after('assignee_id')->constrained()->nullOnDelete();
            $table->timestamp('first_response_at_risk_at')->nullable()->after('sla_policy_id');
            $table->timestamp('first_response_due_at')->nullable()->after('first_response_at_risk_at');
            $table->timestamp('resolution_at_risk_at')->nullable()->after('first_response_due_at');
            $table->timestamp('resolution_due_at')->nullable()->after('resolution_at_risk_at');
            $table->timestamp('first_responded_at')->nullable()->after('resolution_due_at');
            $table->timestamp('sla_paused_at')->nullable()->after('first_responded_at');
            $table->timestamp('first_response_breached_at')->nullable()->after('sla_paused_at');
            $table->timestamp('resolution_breached_at')->nullable()->after('first_response_breached_at');
            $table->unsignedSmallInteger('escalation_level')->default(0)->after('resolution_breached_at');
            $table->timestamp('escalated_at')->nullable()->after('escalation_level');

            $table->index('created_at');
            $table->index('resolution_due_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['resolution_due_at']);
            $table->dropConstrainedForeignId('sla_policy_id');
            $table->dropColumn([
                'first_response_at_risk_at', 'first_response_due_at', 'resolution_at_risk_at', 'resolution_due_at', 'first_responded_at', 'sla_paused_at',
                'first_response_breached_at', 'resolution_breached_at', 'escalation_level', 'escalated_at',
            ]);
        });
    }
};
