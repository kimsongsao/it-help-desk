<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * List the help desk users.
     */
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'integer'],
        ]);

        return view('users.index', [
            'users' => User::query()
                ->when($filters['search'] ?? null, fn ($query, string $search) => $query->where(
                    fn ($query) => $query->whereLike('name', "%{$search}%")->orWhereLike('email', "%{$search}%")
                ))
                ->when($filters['role'] ?? null, fn ($query, int $role) => $query->where('role_id', $role))
                ->withCount(['requestedTickets', 'assignedTickets'])
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString(),
            'roles' => Role::orderBy('id')->get(),
            'filters' => $filters,
        ]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): View
    {
        return view('users.form', [
            'user' => new User(['is_active' => true]),
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $user = new User($request->safe()->except(['role_id', 'is_active']));
        $user->role_id = $request->validated('role_id');
        $user->is_active = $request->validated('is_active');
        $user->save();

        return redirect()->route('users.index')->with('status', __(':name was added.', ['name' => $user->name]));
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    /**
     * Update the user, their role and whether they can sign in.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->fill($request->safe()->except(['role_id', 'is_active', 'password']));
        $user->role_id = $request->validated('role_id');
        $user->is_active = $request->validated('is_active');

        if (filled($request->validated('password'))) {
            $user->password = $request->validated('password');
        }

        $user->save();

        if (! $user->is_active) {
            $user->tokens()->delete();
        }

        return redirect()->route('users.index')->with('status', __(':name was updated.', ['name' => $user->name]));
    }
}
