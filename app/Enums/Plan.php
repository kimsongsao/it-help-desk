<?php

namespace App\Enums;

enum Plan: string
{
    case Free = 'free';
    case Business = 'business';
    case Enterprise = 'enterprise';

    /**
     * Get the translated label for the plan.
     */
    public function label(?string $locale = null): string
    {
        return __(ucfirst($this->value), locale: $locale);
    }

    /**
     * Get the Tabler color used to render the plan.
     */
    public function color(): string
    {
        return match ($this) {
            self::Free => 'secondary',
            self::Business => 'blue',
            self::Enterprise => 'purple',
        };
    }

    /**
     * Get the maximum number of support agents, or null when unlimited.
     */
    public function maxAgents(): ?int
    {
        return match ($this) {
            self::Free => 3,
            self::Business => 25,
            self::Enterprise => null,
        };
    }

    /**
     * Get the features included in the plan.
     *
     * @return list<Feature>
     */
    public function features(): array
    {
        $business = [
            Feature::KnowledgeBase, Feature::SlaManagement, Feature::Workflows, Feature::Reports,
            Feature::Api, Feature::Teams, Feature::EmailChannel,
        ];

        return match ($this) {
            self::Free => [Feature::KnowledgeBase],
            self::Business => $business,
            self::Enterprise => [...$business, Feature::AuditLog, Feature::DataExport, Feature::Sso],
        };
    }

    /**
     * Determine if the plan includes the feature.
     */
    public function includes(Feature $feature): bool
    {
        return in_array($feature, $this->features(), true);
    }
}
