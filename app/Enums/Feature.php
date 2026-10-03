<?php

namespace App\Enums;

enum Feature: string
{
    case SlaManagement = 'sla_management';
    case Workflows = 'workflows';
    case Reports = 'reports';
    case Api = 'api';
    case Teams = 'teams';
    case KnowledgeBase = 'knowledge_base';
    case EmailChannel = 'email_channel';
    case AuditLog = 'audit_log';
    case DataExport = 'data_export';
    case Sso = 'sso';

    /**
     * Get the translated label for the feature.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::SlaManagement => 'SLA policies & escalation',
            self::Workflows => 'Workflow automation',
            self::Reports => 'Reports & analytics',
            self::Api => 'REST API',
            self::Teams => 'Agent teams',
            self::KnowledgeBase => 'Knowledge base',
            self::EmailChannel => 'Email to ticket',
            self::AuditLog => 'Audit log',
            self::DataExport => 'Data export',
            self::Sso => 'Single sign-on (SSO)',
        }, locale: $locale);
    }
}
