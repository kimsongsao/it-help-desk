<?php

namespace App\Enums;

enum EscalationType: string
{
    case Manual = 'manual';
    case Automatic = 'automatic';

    /**
     * Get the translated label for the escalation type.
     */
    public function label(?string $locale = null): string
    {
        return __(ucfirst($this->value), locale: $locale);
    }
}
