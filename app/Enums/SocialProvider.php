<?php

namespace App\Enums;

enum SocialProvider: string
{
    case Google = 'google';
    case Facebook = 'facebook';
    case Telegram = 'telegram';

    /**
     * Get the provider's display name.
     */
    public function label(): string
    {
        return match ($this) {
            self::Google => 'Google',
            self::Facebook => 'Facebook',
            self::Telegram => 'Telegram',
        };
    }

    /**
     * Determine if the provider uses Socialite's OAuth redirect flow.
     */
    public function usesOAuth(): bool
    {
        return $this !== self::Telegram;
    }

    /**
     * Determine if credentials for the provider are configured.
     */
    public function isConfigured(): bool
    {
        return match ($this) {
            self::Telegram => filled(config('services.telegram.bot_token')) && filled(config('services.telegram.bot_username')),
            default => filled(config("services.{$this->value}.client_id")) && filled(config("services.{$this->value}.client_secret")),
        };
    }

    /**
     * Get the providers that users can sign in with.
     *
     * @return list<self>
     */
    public static function enabled(): array
    {
        return array_values(array_filter(self::cases(), fn (self $provider): bool => $provider->isConfigured()));
    }
}
