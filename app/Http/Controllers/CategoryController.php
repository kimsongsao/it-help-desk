<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * List the ticket categories.
     */
    public function index(): View
    {
        return view('categories.index', [
            'categories' => Category::withCount('tickets')->orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for creating a category.
     */
    public function create(): View
    {
        return view('categories.form', ['category' => new Category]);
    }

    /**
     * Store a new category.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('categories.index')->with('status', __('Category created.'));
    }

    /**
     * Show the form for editing a category.
     */
    public function edit(Category $category): View
    {
        return view('categories.form', ['category' => $category]);
    }

    /**
     * Update the category.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('categories.index')->with('status', __('Category updated.'));
    }

    /**
     * Delete the category. Its tickets become uncategorized.
     */
    public function destroy(Category $category): RedirectResponse
    {
        $category->delete();

        return redirect()->route('categories.index')->with('status', __('Category deleted.'));
    }
}
