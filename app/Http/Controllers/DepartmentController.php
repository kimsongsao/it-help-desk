<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    /**
     * List the departments people can belong to.
     */
    public function index(): View
    {
        return view('departments.index', [
            'departments' => Department::withCount('users')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a department.
     */
    public function create(): View
    {
        return view('departments.form', ['department' => new Department]);
    }

    /**
     * Store a new department.
     */
    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        Department::create($request->validated());

        return redirect()->route('departments.index')->with('status', __('Department created.'));
    }

    /**
     * Show the form for editing a department.
     */
    public function edit(Department $department): View
    {
        return view('departments.form', ['department' => $department]);
    }

    /**
     * Update the department.
     */
    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('departments.index')->with('status', __('Department updated.'));
    }

    /**
     * Delete the department. Its people stay, without a department.
     */
    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()->route('departments.index')->with('status', __('Department deleted.'));
    }
}
