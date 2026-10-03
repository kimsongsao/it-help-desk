<?php

namespace App\Enums;

enum WorkflowOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case Contains = 'contains';
    case NotContains = 'not_contains';
    case IsEmpty = 'is_empty';
    case IsNotEmpty = 'is_not_empty';
    case Changed = 'changed';

    /**
     * Get the translated label for the operator.
     */
    public function label(?string $locale = null): string
    {
        return __(match ($this) {
            self::Equals => 'is',
            self::NotEquals => 'is not',
            self::Contains => 'contains',
            self::NotContains => 'does not contain',
            self::IsEmpty => 'is empty',
            self::IsNotEmpty => 'is not empty',
            self::Changed => 'was changed',
        }, locale: $locale);
    }

    /**
     * Determine if the operator compares against a value.
     */
    public function needsValue(): bool
    {
        return ! in_array($this, [self::IsEmpty, self::IsNotEmpty, self::Changed], true);
    }
}
