<?php

namespace App\Http\Requests;

use App\Enums\Permission;
use App\Models\Role;
use Illuminate\Validation\Validator;

class UpdateRoleRequest extends StoreRoleRequest
{
    /**
     * Stop a role from turning its members into agents beyond the plan's agent seats.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Role $role */
                $role = $this->route('role');
                $organization = $this->user()->organization;
                $limit = $organization->plan->maxAgents();
                $grantsWork = in_array(Permission::TicketsWork->value, (array) $this->input('permissions', []), true);

                if ($limit === null || $role->usesAgentSeat() || ! $grantsWork) {
                    return;
                }

                $newAgents = $role->users()->where('is_active', true)->count();

                if ($organization->agentCount() + $newAgents > $limit) {
                    $validator->errors()->add('permissions', $organization->agentSeatLimitMessage());
                }
            },
        ];
    }
}
