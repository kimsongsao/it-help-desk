<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class UpdateUserRequest extends StoreUserRequest
{
    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            ...parent::after(),
            function (Validator $validator): void {
                if (! $this->route('user')->is($this->user())) {
                    return;
                }

                if ((int) $this->input('role_id') !== $this->user()->role_id) {
                    $validator->errors()->add('role_id', __('You cannot change your own role.'));
                }

                if (! $this->boolean('is_active')) {
                    $validator->errors()->add('is_active', __('You cannot deactivate your own account.'));
                }
            },
        ];
    }
}
