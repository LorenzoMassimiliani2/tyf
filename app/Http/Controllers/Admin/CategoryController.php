<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Categories', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        Category::create($data);

        return redirect()->back()->with('success', 'Categoria creata.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $category->update($data);

        return redirect()->back()->with('success', 'Categoria aggiornata.');
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return redirect()->back()->with('success', 'Categoria rimossa.');
    }

    public function destroyByPayload(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:categories,id'],
        ]);

        $category = Category::find($data['id']);
        $category->delete();

        return response()->json(['message' => 'Categoria rimossa.']);
    }

    // Fallback per richieste PATCH senza ID nella path
    public function updateByPayload(Request $request)
    {
        $data = $request->validate([
            'id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:60'],
            'color' => ['nullable', 'string', 'max:20'],
            'is_active' => ['boolean'],
        ]);

        $category = Category::find($data['id']);
        $category->update([
            'name' => $data['name'],
            'color' => $data['color'] ?? null,
            'is_active' => $data['is_active'] ?? false,
        ]);

        return response()->json(['message' => 'Categoria aggiornata.']);
    }
}
