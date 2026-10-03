<?php

namespace App\Http\Resources;

use App\Enums\Permission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isSelf = $request->user()?->is($this->resource);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when($isSelf || $request->user()?->isStaff(), $this->email),
            'department' => $this->department,
            'role' => [
                'id' => $this->role->id,
                'name' => $this->role->name,
                'slug' => $this->role->slug,
            ],
            'permissions' => $this->when($isSelf, fn () => array_values(array_map(
                fn (Permission $permission) => $permission->value,
                array_filter(Permission::cases(), fn (Permission $permission) => $this->hasPermission($permission)),
            ))),
        ];
    }
}
