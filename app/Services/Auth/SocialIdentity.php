<?php

namespace App\Services\Auth;

use App\Enums\SocialProvider;

/**
 * The details of a person as reported by a social login provider.
 */
final readonly class SocialIdentity
{
    public function __construct(
        public SocialProvider $provider,
        public string $id,
        public string $name,
        public ?string $email = null,
        public bool $emailIsVerified = false,
        public ?string $avatarUrl = null,
    ) {}
}
