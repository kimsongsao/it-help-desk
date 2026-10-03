<?php

namespace App\Services\Auth;

use App\Enums\SocialProvider;
use App\Exceptions\SocialLoginException;
use Illuminate\Support\Arr;

/**
 * Verifies the data sent by the Telegram Login Widget.
 *
 * @see https://core.telegram.org/widgets/login#checking-authorization
 */
class TelegramLoginVerifier
{
    /**
     * How long a Telegram authorization stays valid, in seconds.
     */
    private const int MaxAgeSeconds = 86400;

    /**
     * Check the signature and age of the widget data and turn it into a social identity.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws SocialLoginException
     */
    public function verify(array $data): SocialIdentity
    {
        $hash = (string) ($data['hash'] ?? '');
        $fields = Arr::only($data, ['id', 'first_name', 'last_name', 'username', 'photo_url', 'auth_date']);

        ksort($fields);
        $checkString = collect($fields)->map(fn (mixed $value, string $key): string => "{$key}={$value}")->implode("\n");
        $secret = hash('sha256', (string) config('services.telegram.bot_token'), binary: true);

        if ($hash === '' || ! hash_equals(hash_hmac('sha256', $checkString, $secret), $hash)) {
            throw new SocialLoginException(__('The Telegram login could not be verified. Please try again.'));
        }

        if (now()->timestamp - (int) ($fields['auth_date'] ?? 0) > self::MaxAgeSeconds) {
            throw new SocialLoginException(__('The Telegram login has expired. Please try again.'));
        }

        return new SocialIdentity(
            provider: SocialProvider::Telegram,
            id: (string) $fields['id'],
            name: trim(($fields['first_name'] ?? '').' '.($fields['last_name'] ?? '')) ?: (string) ($fields['username'] ?? 'Telegram user'),
            avatarUrl: $fields['photo_url'] ?? null,
        );
    }
}
