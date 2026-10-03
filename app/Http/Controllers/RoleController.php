<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RoleController extends Controller
{
    /**
     * List the roles.
     */
    public function index(): View
    {
        return view('roles.index', [
            'roles' => Role::withCount('users')->orderBy('id')->get(),
        ]);
    }

    /**
     * Show the form for creating a role.
     */
    public function create(): View
    {
        return view('roles.form', ['role' => new Role(['permissions' => []])]);
    }

    /**
     * Store a new role.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        Role::create([
            ...$request->validated(),
            'slug' => Str::slug($request->validated('name')),
            'permissions' => $request->validated('permissions', []),
        ]);

        return redirect()->route('roles.index')->with('status', __('Role created.'));
    }

    /**
     * Show the form for editing a role.
     */
    public function edit(Role $role): View
    {
        return view('roles.form', ['role' => $role]);
    }

    /**
     * Update the role. The administrator role always keeps every permission.
     */
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'name' => $request->validated('name'),
            'description' => $request->validated('description'),
            'permissions' => $role->isAdministrator() ? [] : $request->validated('permissions', []),
        ]);

        return redirect()->route('roles.index')->with('status', __('Role updated.'));
    }

    /**
     * Delete a custom role that no user holds.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->is_system || $role->users()->exists()) {
            return back()->withErrors(['role' => __('Built-in roles and roles that are still assigned to users cannot be deleted.')]);
        }

        $role->delete();

        return redirect()->route('roles.index')->with('status', __('Role deleted.'));
    }
}
