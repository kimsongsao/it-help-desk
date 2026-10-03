<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class AssignableUser implements ValidationRule
{
    /**
     * Ensure the value is the id of an active user who can work on tickets.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value) || ! User::assignable()->whereKey((int) $value)->exists()) {
            $fail('validation.assignable_user')->translate();
        }
    }
}
